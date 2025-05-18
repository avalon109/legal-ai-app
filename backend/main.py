from fastapi import FastAPI, UploadFile, File, Form
from fastapi.middleware.cors import CORSMiddleware
from typing import List
import json
from pydantic import BaseModel
from gemini_client import get_ai_explanation
from legal_crew.legal_crew import LegalCrew
import logging

app = FastAPI()

# Configure CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Allows all origins
    allow_credentials=True,
    allow_methods=["*"],  # Allows all methods
    allow_headers=["*"],  # Allows all headers
)

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class MessageRequest(BaseModel):
    message: str

class LegalQuestion(BaseModel):
    question: str
    contract_text: str | None = None

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

@app.get("/ai-explanation")
async def get_explanation():
    """
    Endpoint to get an AI explanation using Gemini
    """
    try:
        explanation = get_ai_explanation()
        return {
            "status": "success",
            "explanation": explanation
        }
    except Exception as e:
        return {
            "status": "error",
            "message": str(e)
        }

@app.post("/legal-advice")
async def get_legal_advice(request: LegalQuestion):
    """
    Endpoint to get legal advice using our crew of legal experts
    """
    try:
        logger.info(f"Received legal advice request: {request.question}")
        legal_crew = LegalCrew()
        result = legal_crew.process_question(request.question)
        logger.info(f"Legal advice response: {result}")

        # Check if we need the contract
        if "CONTRACT_NEEDED" in result:
            return {
                "status": "contract_needed",
                "message": result
            }

        return {
            "status": "success",
            "result": result
        }
    except Exception as e:
        logger.error(f"Error processing legal advice request: {str(e)}", exc_info=True)
        return {
            "status": "error",
            "message": str(e)
        }

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
