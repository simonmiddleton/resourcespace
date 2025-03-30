import argparse
import getpass
import torch
import clip
import uvicorn
from fastapi import FastAPI, File, UploadFile, Form, HTTPException
from fastapi.responses import JSONResponse
from PIL import Image
import numpy as np
import io
import mysql.connector

# Command-line arguments
parser = argparse.ArgumentParser(description="CLIP search service for ResourceSpace")
parser.add_argument("--dbuser", help="MySQL username")
parser.add_argument("--dbpass", help="MySQL password")
parser.add_argument("--host", default="0.0.0.0", help="Host to run on")
parser.add_argument("--port", type=int, default=8000, help="Port to run on")
args = parser.parse_args()

if not args.dbuser:
    args.dbuser = input("Enter MySQL username (or use --dbuser): ")

if not args.dbpass:
    args.dbpass = getpass.getpass("Enter MySQL password (or use --dbpass): ")

# Global DB credentials (used later)
DB_CONFIG = {
    "host": "localhost",
    "user": args.dbuser,
    "password": args.dbpass
}

# Set up FastAPI app and in-memory cache
app = FastAPI()
device = "cpu"
print("🔌 Loading CLIP model...")
model, preprocess = clip.load("ViT-B/32", device=device)
print("✅ Model loaded.")

cached_vectors = {}  # { db_name: (vectors_np, resource_ids) }

def load_vectors_for_db(db_name):
    if db_name in cached_vectors:
        return cached_vectors[db_name]

    print(f"🔄 Loading vectors from DB: {db_name}")
    try:
        conn = mysql.connector.connect(**DB_CONFIG, database=db_name)
        cursor = conn.cursor()
        cursor.execute("SELECT resource, vector FROM resource_clip_vector")
        rows = cursor.fetchall()
        conn.close()
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"DB error: {e}")

    resource_ids = []
    vectors = []

    for resource, vector_json in rows:
        vector = np.array(eval(vector_json), dtype=np.float32)
        vector /= np.linalg.norm(vector)
        vectors.append(vector)
        resource_ids.append(resource)

    if vectors:
        vectors_np = np.stack(vectors)
    else:
        vectors_np = np.empty((0, 512), dtype=np.float32)

    cached_vectors[db_name] = (vectors_np, resource_ids)
    print(f"✅ Cached {len(resource_ids)} vectors for DB: {db_name}")
    return cached_vectors[db_name]

@app.post("/vector")
async def generate_vector(
    image: UploadFile = File(...),
):
    try:
        contents = await image.read()
        img = Image.open(io.BytesIO(contents)).convert("RGB")
        img_input = preprocess(img).unsqueeze(0).to(device)

        with torch.no_grad():
            vector = model.encode_image(img_input)
            vector = vector / vector.norm(dim=-1, keepdim=True)
            vector_np = vector.cpu().numpy().flatten().tolist()

        return JSONResponse(content=vector_np)

    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Vector generation error: {e}")

@app.post("/search")
async def search(
    db: str = Form(...),
    text: str = Form(None),
    image: UploadFile = File(None),
    top_k: int = Form(5)
):
    if not text and not image:
        raise HTTPException(status_code=400, detail="Provide either text or image")

    vectors, resource_ids = load_vectors_for_db(db)

    if len(resource_ids) == 0:
        return JSONResponse(content=[])

    try:
        if text:
            tokens = clip.tokenize([text]).to(device)
            with torch.no_grad():
                query_vector = model.encode_text(tokens)
        else:
            contents = await image.read()
            img = Image.open(io.BytesIO(contents)).convert("RGB")
            img_input = preprocess(img).unsqueeze(0).to(device)
            with torch.no_grad():
                query_vector = model.encode_image(img_input)

        query_vector = query_vector / query_vector.norm(dim=-1, keepdim=True)
        query_np = query_vector.cpu().numpy().flatten()

        sims = vectors @ query_np
        top_indices = sims.argsort()[-top_k:][::-1]

        results = [
            {"resource": int(resource_ids[i]), "score": float(sims[i])}
            for i in top_indices
        ]

        return JSONResponse(content=results)

    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Search error: {e}")


@app.post("/similar")
async def find_similar(
    db: str = Form(...),
    resource: int = Form(...),
    top_k: int = Form(5)
):
    vectors, resource_ids = load_vectors_for_db(db)

    if len(resource_ids) == 0:
        return JSONResponse(content=[])

    try:
        # Connect to DB to fetch the vector for the specified resource
        conn = mysql.connector.connect(**DB_CONFIG, database=db)
        cursor = conn.cursor()
        cursor.execute("SELECT vector FROM resource_clip_vector WHERE resource = %s", (resource,))
        row = cursor.fetchone()
        conn.close()

        if not row:
            raise HTTPException(status_code=404, detail=f"Vector not found for resource {resource}")

        query_vector = np.array(eval(row[0]), dtype=np.float32)
        query_vector /= np.linalg.norm(query_vector)

        # Calculate cosine similarity against all vectors
        sims = vectors @ query_vector

        # Exclude the query resource itself
        filtered = [
            (i, sim) for i, sim in enumerate(sims)
            if resource_ids[i] != resource
        ]

        # Get top matches
        top_matches = sorted(filtered, key=lambda x: x[1], reverse=True)[:top_k]

        results = [
            {"resource": int(resource_ids[i]), "score": float(sim)}
            for i, sim in top_matches
        ]

        return JSONResponse(content=results)

    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Similarity error: {e}")



# Start the server
if __name__ == "__main__":
    uvicorn.run(app, host=args.host, port=args.port, log_level="info")

