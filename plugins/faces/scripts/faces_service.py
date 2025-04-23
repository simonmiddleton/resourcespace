from fastapi import FastAPI, UploadFile, File, HTTPException, Request
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import insightface
from insightface.app import FaceAnalysis
import numpy as np
import cv2
import uvicorn
import faiss
import argparse
import mysql.connector
from datetime import datetime, timedelta

# Command-line arguments
parser = argparse.ArgumentParser()
parser.add_argument("--db-host", default="localhost")
parser.add_argument("--db-user", default="root")
parser.add_argument("--db-pass", default="")
parser.add_argument("--port", default=8001, type=int)
args, unknown = parser.parse_known_args()

# Initialise FastAPI app
app = FastAPI()

# Allow cross-origin requests if needed (optional)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Initialise InsightFace (CPU-only)
face_app = FaceAnalysis(name='buffalo_l')
face_app.prepare(ctx_id=-1)  # Use CPU

# Dictionary to hold FAISS index and metadata per database
db_indexes = {}

# DB connection helper
def get_mysql_connection(db_name):
    return mysql.connector.connect(
        host=args.db_host,
        database=db_name,
        user=args.db_user,
        password=args.db_pass
    )

# Load vectors from MySQL for a given database
def load_vectors(db_name):
    conn = get_mysql_connection(db_name)
    cursor = conn.cursor()
    cursor.execute("SELECT ref, resource, vector_blob, node FROM resource_face")
    results = cursor.fetchall()
    conn.close()

    if not results:
        print(f"No face vectors found in database '{db_name}'.")
        return

    vectors = []
    index_to_metadata = []
    max_ref = 0

    for ref, resource, blob, node in results:
        vector = np.frombuffer(blob, dtype=np.float32)
        vector = vector / np.linalg.norm(vector)
        vectors.append(vector)
        index_to_metadata.append({"ref": ref, "resource": resource, "node": node})
        max_ref = max(max_ref, ref)

    d = len(vectors[0])
    index = faiss.IndexFlatIP(d)
    index.add(np.array(vectors).astype('float32'))

    db_indexes[db_name] = {
        "index": index,
        "metadata": index_to_metadata,
        "vectors": np.array(vectors).astype('float32'),
        "last_used": datetime.utcnow(),
        "max_ref": max_ref
    }
    print(f"Loaded {len(vectors)} vectors for database '{db_name}'.")

# Request model for similarity search
class FaceSearchRequest(BaseModel):
    ref: int
    db: str
    threshold: float = 0.0
    k: int = 10

@app.post("/extract_faces")
async def extract_faces(file: UploadFile = File(...)):
    try:
        contents = await file.read()
        image = cv2.imdecode(np.frombuffer(contents, np.uint8), cv2.IMREAD_COLOR)
        if image is None:
            raise ValueError("Could not decode image")

        faces = face_app.get(image)
        results = []
        for face in faces:
            results.append({
                "bbox": face.bbox.tolist(),
                "embedding": face.embedding.tolist(),
                "det_score": float(face.det_score)
            })
        return results
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/find_similar_faces")
async def find_similar_faces(request: FaceSearchRequest):
    db_name = request.db
    now = datetime.utcnow()

    # Determine if the cache should be refreshed
    should_reload = False

    if db_name not in db_indexes:
        should_reload = True
    else:
        last_used = db_indexes[db_name].get("last_used")
        max_known_ref = db_indexes[db_name].get("max_ref", 0)

        if now - last_used > timedelta(hours=1):
            print(f"Cache for '{db_name}' is older than 1 hour. Refreshing.")
            should_reload = True
        else:
            # Check if there are new face entries
            conn = get_mysql_connection(db_name)
            cursor = conn.cursor()
            cursor.execute("SELECT MAX(ref) FROM resource_face")
            row = cursor.fetchone()
            conn.close()
            latest_ref = row[0] if row and row[0] is not None else 0
            if latest_ref > max_known_ref:
                print(f"New faces detected in '{db_name}'. Reloading vectors.")
                should_reload = True

    if should_reload:
        load_vectors(db_name)

    if db_name not in db_indexes:
        raise HTTPException(status_code=500, detail=f"Unable to load vector index for database '{db_name}'")

    db_indexes[db_name]["last_used"] = now

    conn = get_mysql_connection(db_name)
    cursor = conn.cursor()
    cursor.execute("SELECT vector_blob FROM resource_face WHERE ref = %s", (request.ref,))
    row = cursor.fetchone()
    conn.close()

    if not row:
        raise HTTPException(status_code=404, detail="Face vector not found")

    query_vector = np.frombuffer(row[0], dtype=np.float32)
    query_vector = query_vector / np.linalg.norm(query_vector)
    query_vector = query_vector.reshape(1, -1)

    face_index = db_indexes[db_name]["index"]
    metadata = db_indexes[db_name]["metadata"]

    distances, indices = face_index.search(query_vector, request.k + 1)

    matches = []
    for dist, idx in zip(distances[0], indices[0]):
        if idx < 0 or idx >= len(metadata):
            continue

        match = metadata[idx].copy()

        if match["ref"] == request.ref:
            continue

        similarity = float(round(dist, 4))
        if similarity >= request.threshold:
            match["similarity"] = similarity
            matches.append(match)
            print(f"Match: ref={match['ref']} similarity={match['similarity']:.4f}")

    matches.sort(key=lambda x: -x["similarity"])
    return matches

if __name__ == "__main__":
    uvicorn.run("faces_service:app", host="0.0.0.0", port=args.port)