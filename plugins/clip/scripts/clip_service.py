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
loaded_max_ref = {}  # { db_name: max_ref }

def load_vectors_for_db(db_name, force_reload=False):
    global cached_vectors, loaded_max_ref

    if db_name in cached_vectors and not force_reload:
        return cached_vectors[db_name]

    print(f"🔄 Loading vectors from DB: {db_name}")
    try:
        conn = mysql.connector.connect(**DB_CONFIG, database=db_name)
        cursor = conn.cursor()

        # Track last max ref to only load new
        last_ref = loaded_max_ref.get(db_name, 0)
        cursor.execute(
            "SELECT resource, vector FROM resource_clip_vector WHERE resource > %s ORDER BY resource",
            (last_ref,)
        )
        rows = cursor.fetchall()
        conn.close()
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"DB error: {e}")

    if not rows:
        return cached_vectors.get(db_name, (np.empty((0, 512)), []))

    # Load new vectors
    new_vectors = []
    new_ids = []

    for resource, vector_json in rows:
        vector = np.array(eval(vector_json), dtype=np.float32)
        vector /= np.linalg.norm(vector)
        new_vectors.append(vector)
        new_ids.append(resource)

    if db_name in cached_vectors:
        old_vectors, old_ids = cached_vectors[db_name]
        all_vectors = np.vstack([old_vectors, new_vectors])
        all_ids = old_ids + new_ids
    else:
        all_vectors = np.stack(new_vectors)
        all_ids = new_ids

    cached_vectors[db_name] = (all_vectors, all_ids)
    loaded_max_ref[db_name] = max(all_ids)

    print(f"✅ Updated cache: {len(all_ids)} vectors for DB: {db_name}")
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




import threading
import time

def background_vector_loader():
    while True:
        time.sleep(30)
        try:
            for db_name in cached_vectors.keys():
                load_vectors_for_db(db_name, force_reload=True)
        except Exception as e:
            print(f"⚠️ Background update failed: {e}")

@app.on_event("startup")
def start_background_task():
    print("🌀 Starting background vector refresher thread...")
    thread = threading.Thread(target=background_vector_loader, daemon=True)
    thread.start()




# Start the server
if __name__ == "__main__":
    uvicorn.run(app, host=args.host, port=args.port, log_level="info")

