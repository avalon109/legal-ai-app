from crewai import Agent, Task, Crew, Process
from google import genai
import os
from dotenv import load_dotenv
from pathlib import Path
from .laws_database import LAWS_DATABASE
from langchain_google_genai import ChatGoogleGenerativeAI

# Load environment variables
env_path = Path(__file__).parent.parent.parent / '.env'
load_dotenv(env_path)

# Initialize Gemini client
client = genai.Client(api_key=os.getenv('GEMINI_API_KEY'))

# Create Gemini LLM
gemini_llm = ChatGoogleGenerativeAI(
    model="gemini-2.0-flash",
    google_api_key=os.getenv('GEMINI_API_KEY'),
    temperature=0.7
)

# Load overview.txt
overview_path = Path(__file__).parent.parent / 'laws' / 'overview.txt'
with open(overview_path, 'r', encoding='utf-8') as f:
    OVERVIEW_TEXT = f.read()

class LegalCrew:
    def __init__(self):
        # Initialize the easy answer agent
        self.easy_answer_agent = Agent(
            role='Easy Answer Finder',
            goal='Check if a question can be answered directly from the overview document',
            backstory="""You are an expert at quickly scanning legal overview documents to find 
            direct answers to common questions. Your specialty is identifying when a question 
            can be answered directly from the overview without needing deeper legal analysis.""",
            verbose=True,
            allow_delegation=True,
            llm=gemini_llm
        )

        # Initialize the expert legal agent
        self.legal_expert = Agent(
            role='Legal Expert',
            goal='Provide detailed legal analysis and answers for complex questions',
            backstory="""You are a senior legal expert with deep knowledge of tenant rights law. 
            You excel at analyzing complex legal questions and providing comprehensive answers 
            based on detailed legal texts and precedents.""",
            verbose=True,
            allow_delegation=True,
            llm=gemini_llm
        )

    def create_tasks(self, question):
        # Task 1: Check if question can be answered from overview
        easy_answer_task = Task(
            description=f"""Check if the following question can be answered directly from the 
            overview document. If you find a clear answer, return it. If not, return 'NEEDS_EXPERT'.
            
            Question: {question}
            
            Overview Document: {OVERVIEW_TEXT}""",
            agent=self.easy_answer_agent
        )

        # Task 2: Expert legal analysis if needed
        expert_task = Task(
            description=f"""If the previous agent returned 'NEEDS_EXPERT', provide a detailed 
            legal analysis and answer for this question using all available legal texts.
            
            Question: {question}
            Available Laws: {LAWS_DATABASE}""",
            agent=self.legal_expert
        )

        return [easy_answer_task, expert_task]

    def process_question(self, question):
        # Create tasks for the question
        tasks = self.create_tasks(question)

        # First, run just the easy answer task
        easy_answer_crew = Crew(
            agents=[self.easy_answer_agent],
            tasks=[tasks[0]],
            verbose=2,
            process=Process.sequential
        )
        
        easy_answer_result = easy_answer_crew.kickoff()
        
        # If we got a direct answer, return it
        if easy_answer_result != 'NEEDS_EXPERT':
            return easy_answer_result

        # If we need expert analysis, proceed with that
        expert_crew = Crew(
            agents=[self.legal_expert],
            tasks=[tasks[1]],
            verbose=2,
            process=Process.sequential
        )

        # Get the expert result
        result = expert_crew.kickoff()
        return result 