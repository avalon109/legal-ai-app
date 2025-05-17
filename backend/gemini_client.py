from google import genai
import os
from dotenv import load_dotenv, dotenv_values
from pathlib import Path

def get_gemini_api_key():
    """
    Get the Gemini API key from either environment variables or .env file.
    Prioritizes environment variables for production use.
    """
    # Print all environment variables for debugging (excluding sensitive values)
    print("Environment variables available:", [k for k in os.environ.keys() if 'KEY' not in k])
    
    # Try to get from environment variable first (production)
    api_key = os.environ.get('GEMINI_API_KEY')  # Changed from os.getenv to os.environ.get
    print(f"Initial API key from env: {'Found' if api_key else 'Not found'}")
    
    # If not found in environment, try loading from .env file (local development)
    if not api_key:
        # Look for .env in parent directory
        env_path = Path(__file__).parent.parent / '.env'
        print(f"Looking for .env file at: {env_path}")
        print(f"File exists: {env_path.exists()}")
        print(f"Absolute path: {env_path.absolute()}")
        
        # Try direct file reading first
        try:
            with open(env_path, 'r') as f:
                print("Raw .env file contents:")
                content = f.read()
                print(content)
                print("Looking for GEMINI_API_KEY in content:", "GEMINI_API_KEY" in content)
        except Exception as e:
            print(f"Error reading .env file: {e}")
        
        # Now try python-dotenv
        print("\nTrying python-dotenv:")
        env_values = dotenv_values(env_path)
        print(f"All .env values from dotenv: {env_values}")
        print(f"GEMINI_API_KEY in dotenv values: {'GEMINI_API_KEY' in env_values}")
        
        load_dotenv(env_path, override=True)
        api_key = os.environ.get('GEMINI_API_KEY')  # Changed from os.getenv to os.environ.get
        print(f"API key after loading .env: {'Found' if api_key else 'Not found'}")
    
    if not api_key:
        raise ValueError("GEMINI_API_KEY not found in environment variables or .env file")
    
    return api_key

def get_ai_explanation():
    # Initialize the Gemini client with API key
    client = genai.Client(api_key=get_gemini_api_key())
    
    # Make the API call
    response = client.models.generate_content(
        model="gemini-2.0-flash",
        contents="Explain how AI works in a few words"
    )
    
    return response.text

if __name__ == "__main__":
    # Test the function
    explanation = get_ai_explanation()
    print("AI Explanation:", explanation) 