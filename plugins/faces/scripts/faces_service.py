from fastapi import FastAPI, UploadFile, File, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import insightface
from insightface.app import FaceAnalysis
import numpy as np
import cv2
import uvicorn

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

# Request model for similarity search
class FaceSearchRequest(BaseModel):
    vector: list[float]
    threshold: float = 0.6

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
    # Stub - to be implemented with FAISS or database vector search
    return {"message": "Similarity search not yet implemented", "input_vector_dim": len(request.vector)}

if __name__ == "__main__":
    uvicorn.run("face_service:app", host="0.0.0.0", port=8001)
