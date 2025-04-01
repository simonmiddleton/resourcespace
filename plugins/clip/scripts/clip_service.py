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
from typing import Optional
import time
import faiss


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

cached_vectors = {}       # { db_name: (vectors_np, resource_ids) }
loaded_max_ref = {}       # { db_name: max_ref }
faiss_indexes = {}        # { db_name: faiss.IndexFlatIP }

def load_vectors_for_db(db_name, force_reload=False):
    global cached_vectors, loaded_max_ref, faiss_indexes

    # Use existing cache if not forcing reload
    if db_name in cached_vectors and not force_reload:
        return cached_vectors[db_name]

    print(f"🔄 Loading vectors from DB: {db_name}")
    try:
        conn = mysql.connector.connect(**DB_CONFIG, database=db_name)
        cursor = conn.cursor()

        last_ref = loaded_max_ref.get(db_name, 0)
        cursor.execute(
            "SELECT resource, vector FROM resource_clip_vector WHERE is_text=0 and resource > %s ORDER BY resource",
            (last_ref,)
        )
        rows = cursor.fetchall()
        conn.close()
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"DB error: {e}")

    if not rows and db_name in cached_vectors:
        return cached_vectors[db_name]

    new_vectors = []
    new_ids = []

    for resource, vector_json in rows:
        try:
            vector = np.array(eval(vector_json), dtype=np.float32)
            vector /= np.linalg.norm(vector)
        except Exception:
            continue  # skip malformed vectors

        new_vectors.append(vector)
        new_ids.append(resource)

    if db_name in cached_vectors:
        old_vectors, old_ids = cached_vectors[db_name]
        vectors = np.vstack([old_vectors, new_vectors])
        ids = old_ids + new_ids
    else:
        vectors = np.stack(new_vectors) if new_vectors else np.empty((0, 512), dtype=np.float32)
        ids = new_ids

    cached_vectors[db_name] = (vectors, ids)
    if ids:
        loaded_max_ref[db_name] = max(ids)

    # Rebuild or update FAISS index
    if db_name not in faiss_indexes:
        index = faiss.IndexFlatIP(512)  # cosine similarity
        if len(vectors) > 0:
            index.add(vectors)
        faiss_indexes[db_name] = index
    else:
        if new_vectors:
            faiss_indexes[db_name].add(np.stack(new_vectors))

    print(f"✅ Cached {len(ids)} vectors for DB: {db_name}")
    return cached_vectors[db_name]



@app.post("/vector")
async def generate_vector(
    image: Optional[UploadFile] = File(None),
    text: Optional[str] = Form(None),
):
    if image is None and text is None:
        raise HTTPException(status_code=400, detail="Provide either 'image' or 'text'")

    try:
        if image:
            contents = await image.read()
            img = Image.open(io.BytesIO(contents)).convert("RGB")
            img_input = preprocess(img).unsqueeze(0).to(device)

            with torch.no_grad():
                vector = model.encode_image(img_input)
        else:
            tokens = clip.tokenize([text]).to(device)
            with torch.no_grad():
                vector = model.encode_text(tokens)

        # Normalise and return vector
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

    print(f"▶️ SEARCH: db={db}, top_k={top_k}")

    vectors, resource_ids = load_vectors_for_db(db)
    print(f"🧠 Vectors loaded: {len(resource_ids)} resources")

    if len(resource_ids) == 0:
        return JSONResponse(content=[])

    try:
        if text:
            print("🔤 Text query")
            tokens = clip.tokenize([text]).to(device)
            with torch.no_grad():
                query_vector = model.encode_text(tokens)
        else:
            print("🖼️ Image query")
            contents = await image.read()
            img = Image.open(io.BytesIO(contents)).convert("RGB")
            img_input = preprocess(img).unsqueeze(0).to(device)
            with torch.no_grad():
                query_vector = model.encode_image(img_input)

        query_vector = query_vector / query_vector.norm(dim=-1, keepdim=True)
        query_np = query_vector.cpu().numpy().flatten()
        print("✅ Query vector created")

        index = faiss_indexes.get(db)
        if not index:
            raise HTTPException(status_code=500, detail="FAISS index not found")

        print("🔍 Performing FAISS search")
        print(f"FAISS index size: {index.ntotal}")
        start = time.time()
        D, I = index.search(query_np.reshape(1, -1), top_k)
        elapsed_ms = round((time.time() - start) * 1000)
        print(f"FAISS search took {elapsed_ms}ms")
        print(f"🎯 Search results: {I[0]}")

        results = [
            {"resource": int(resource_ids[i]), "score": float(D[0][j])}
            for j, i in enumerate(I[0])
        ]

        print("Returning", len(results), "results")
        return JSONResponse(content=results)

    except Exception as e:
        print(f"❌ Exception in /search: {e}")
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

        index = faiss_indexes.get(db)
        if not index:
            raise HTTPException(status_code=500, detail="FAISS index not found")

        # Perform similarity search
        D, I = index.search(query_vector.reshape(1, -1), top_k + 1)  # +1 to skip self
        top_indices = I[0]
        top_indices = [i for i in top_indices if i >= 0]

        sims = D[0]

        results = []
        for i, score in zip(top_indices, sims):
            candidate_id = int(resource_ids[i])
            if candidate_id == resource:
                continue  # Skip the same resource
            results.append({
                "resource": candidate_id,
                "score": float(score)
            })
            if len(results) == top_k:
                break

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
    uvicorn.run(app, host=args.host, port=args.port, log_level="debug")

