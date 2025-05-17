from fastapi import FastAPI, UploadFile, File, Form
from fastapi.middleware.cors import CORSMiddleware
from typing import List
import json
from pydantic import BaseModel

app = FastAPI()

# Configure CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Allows all origins
    allow_credentials=True,
    allow_methods=["*"],  # Allows all methods
    allow_headers=["*"],  # Allows all headers
)

class MessageRequest(BaseModel):
    message: str

@app.get("/")
async def root():
    return {"message": "Hello from the API!"}

@app.post("/message")
async def receive_message(request: MessageRequest):
    """
    Endpoint to receive a simple message
    """
    return {
        "status": "success",
        "message": request.message,
        "received_at": "timestamp"  # You might want to add actual timestamp here
    }

@app.post("/message-with-files")
async def receive_message_with_files(
    message: str = Form(...),
    files: List[UploadFile] = File(...)
):
    """
    Endpoint to receive a message with one or more files
    """
    # Process the files
    file_details = []
    for file in files:
        file_details.append({
            "filename": file.filename,
            "content_type": file.content_type,
            "size": 0  # You might want to add actual file size here
        })
    
    return {
        "status": "success",
        "message": message,
        "files": file_details,
        "received_at": "timestamp"  # You might want to add actual timestamp here
    }

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
