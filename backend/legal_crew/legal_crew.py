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

class LegalCrew:
    def __init__(self):
        # Initialize the three specialized agents
        self.legal_assessor = Agent(
            role='Legal Question Assessor',
            goal='Determine if a legal question can be answered using existing laws',
            backstory="""You are an expert legal assessor with years of experience in quickly 
            determining the nature of legal questions. Your specialty is in identifying whether 
            a question can be answered using existing laws or if it requires additional information.""",
            verbose=True,
            allow_delegation=True,
            llm=gemini_llm
        )

        self.law_selector = Agent(
            role='Law Selection Expert',
            goal='Select relevant laws and case law for a given legal question',
            backstory="""You are a senior legal researcher with extensive knowledge of various 
            legal domains. Your expertise lies in identifying the most relevant laws, case law, 
            and legal commentaries for any given legal question.""",
            verbose=True,
            allow_delegation=True,
            llm=gemini_llm
        )

        self.legal_researcher = Agent(
            role='Legal Research Analyst',
            goal='Analyze selected laws and provide a comprehensive legal answer',
            backstory="""You are a skilled legal analyst with a talent for interpreting laws 
            and case law. You excel at providing clear, accurate legal answers supported by 
            relevant legal texts and precedents.""",
            verbose=True,
            allow_delegation=True,
            llm=gemini_llm
        )

    def create_tasks(self, question):
        # Task 1: Assess if the question can be answered with existing laws
        assessment_task = Task(
            description=f"""Analyze the following legal question and determine if it can be 
            answered using existing laws. Return only 'true' or 'false'.
            
            Question: {question}""",
            agent=self.legal_assessor
        )

        # Task 2: Select relevant laws
        selection_task = Task(
            description=f"""Based on the following legal question, select the most relevant 
            laws, case law, and commentaries from our database. Return the selected laws in a 
            structured format.
            
            Question: {question}
            Available Laws: {LAWS_DATABASE}""",
            agent=self.law_selector
        )

        # Task 3: Research and provide answer
        research_task = Task(
            description=f"""Using the selected laws and the original question, provide a 
            comprehensive legal answer. Include specific references to laws and case law that 
            support your answer.
            
            Question: {question}
            Selected Laws: [Will be provided by previous task]""",
            agent=self.legal_researcher
        )

        return [assessment_task, selection_task, research_task]

    def process_question(self, question):
        # Create tasks for the question
        tasks = self.create_tasks(question)

        # Create and run the crew
        crew = Crew(
            agents=[self.legal_assessor, self.law_selector, self.legal_researcher],
            tasks=tasks,
            verbose=2,
            process=Process.sequential
        )

        # Get the result
        result = crew.kickoff()
        return result 