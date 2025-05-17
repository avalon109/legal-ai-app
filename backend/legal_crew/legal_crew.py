from crewai import Agent, Task, Crew, Process
from google import genai
import os
from dotenv import load_dotenv
from pathlib import Path
from .laws_database import LAWS_DATABASE
from langchain_google_genai import ChatGoogleGenerativeAI
from .voting_tracker import VotingTracker, Vote
import logging

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

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
        logger.info("Initializing LegalCrew")
        # Initialize the easy answer agent
        self.easy_answer_agent = Agent(
            role='Easy Answer Finder',
            goal='Check if a question can be answered directly from the overview document',
            backstory="""You are an expert at quickly scanning legal overview documents to find 
            direct answers to common questions. Your specialty is identifying when a question 
            can be answered directly from the overview without needing deeper legal analysis.""",
            verbose=True,
            allow_delegation=False,
            llm=gemini_llm
        )

        # Initialize the law selector agent
        self.law_selector = Agent(
            role='Law Selector',
            goal='Select the most relevant laws for analyzing a specific legal question',
            backstory="""You are an expert at analyzing legal questions and identifying which 
            specific laws and regulations would be most relevant for providing a comprehensive 
            answer. You understand the relationships between different laws and can prioritize 
            which ones are most likely to contain relevant information.""",
            verbose=True,
            allow_delegation=False,
            llm=gemini_llm
        )

        # Initialize the tenant's lawyer
        self.tenant_lawyer = Agent(
            role='Tenant Rights Lawyer',
            goal='Argue the case from the tenant\'s perspective',
            backstory="""You are a passionate advocate for tenant rights with extensive experience 
            in housing law. You excel at finding and interpreting laws that protect tenant rights 
            and can present compelling arguments in favor of tenant protections.""",
            verbose=True,
            allow_delegation=False,
            llm=gemini_llm
        )

        # Initialize the landlord's lawyer
        self.landlord_lawyer = Agent(
            role='Landlord Rights Lawyer',
            goal='Argue the case from the landlord\'s perspective',
            backstory="""You are a skilled property law attorney who specializes in representing 
            landlords. You have deep knowledge of property rights and can effectively argue for 
            landlord protections while maintaining professional objectivity.""",
            verbose=True,
            allow_delegation=False,
            llm=gemini_llm
        )

        # Initialize the judges
        self.left_judge = Agent(
            role='Left-Leaning Judge',
            goal='Evaluate arguments with a slight bias towards tenant protections',
            backstory="""You are a senior judge with decades of experience in housing law. 
            While you have a slight bias towards protecting tenant rights, you are committed 
            to fair and impartial judgment based on the law.""",
            verbose=True,
            allow_delegation=False,
            llm=gemini_llm
        )

        self.right_judge = Agent(
            role='Right-Leaning Judge',
            goal='Evaluate arguments with a slight bias towards property rights',
            backstory="""You are a young, progressive judge who believes strongly in property 
            rights. While you have a slight bias towards protecting landlord interests, you 
            are committed to fair and impartial judgment based on the law.""",
            verbose=True,
            allow_delegation=False,
            llm=gemini_llm
        )

        self.centrist_judge = Agent(
            role='Centrist Judge',
            goal='Evaluate arguments with balanced perspective',
            backstory="""You are a balanced and experienced judge who carefully weighs both 
            sides of every argument. You are known for your fair and impartial judgments 
            that carefully consider both tenant and landlord rights.""",
            verbose=True,
            allow_delegation=False,
            llm=gemini_llm
        )

    def create_tasks(self, question, selected_law_texts=None, tenant_argument=None, landlord_argument=None):
        logger.info("Creating tasks for question: %s", question)
        
        # Task 1: Easy Answer
        easy_answer_task = Task(
            description=f"""Check if the following question can be answered directly from the
            overview document. If you find a clear answer, return it. If not, return 'NEEDS_EXPERT'.
            Question: {question}
            Overview Document: {OVERVIEW_TEXT}""",
            agent=self.easy_answer_agent
        )
        logger.debug("Created easy_answer_task: %s", easy_answer_task)

        # Task 2: Select relevant laws
        law_selection_task = Task(
            description=f"""Analyze the question and select the most relevant laws from the
            AVAILABLE LAWS list. Return a list of the most relevant law titles, ordered by relevance.
            Ensure the output is ONLY a list of strings (law titles).
            Question: {question}
            AVAILABLE LAWS:
            - Bouwbesluit 2012
            - Leegstandwet
            - Besluit bouwwerken leefomgeving
            - Wet goed verhuurderschap
            - Besluit kleine herstellingen
            - Regeling Bouwbesluit 2012
            - Besluit mandaat en machtiging Toelatingsorganisatie Kwaliteitsborging Bouw
            - Uitvoeringsregeling huurprijzen woonruimte
            - Woningwet
            - Besluit servicekosten
            - Huurprijzenwet woonruimte
            - Uitvoeringswet huurprijzen woonruimte
            - Besluit huurprijzen woonruimte
            - Wet op het overleg huurders verhuurder
            - Burgerlijk Wetboek Boek 5
            - Huisvestingswet 2014""",
            agent=self.law_selector
        )
        logger.debug("Created law_selection_task: %s", law_selection_task)

        tasks = [easy_answer_task, law_selection_task]

        # Only create these tasks if we have the required data
        if selected_law_texts is not None:
            # Task 3: Tenant's lawyer presents case
            tenant_case_task = Task(
                description=f"""Based on the user's QUESTION and the provided SELECTED_LAW_DETAILS,
                present a compelling argument from the tenant's perspective.
                Focus on how these laws protect tenant rights in this situation.
                Do NOT attempt to delegate this task or ask other agents for the law text; use the provided SELECTED_LAW_DETAILS.

                QUESTION: {question}
                SELECTED_LAW_DETAILS: {selected_law_texts}""",
                agent=self.tenant_lawyer
            )
            logger.debug("Created tenant_case_task: %s", tenant_case_task)
            tasks.append(tenant_case_task)

            # Task 4: Landlord's lawyer presents case
            landlord_case_task = Task(
                description=f"""Based on the user's QUESTION and the provided SELECTED_LAW_DETAILS,
                present a compelling argument from the landlord's perspective.
                Focus on how these laws protect landlord rights in this situation.
                Do NOT attempt to delegate this task or ask other agents for the law text; use the provided SELECTED_LAW_DETAILS.

                QUESTION: {question}
                SELECTED_LAW_DETAILS: {selected_law_texts}""",
                agent=self.landlord_lawyer
            )
            logger.debug("Created landlord_case_task: %s", landlord_case_task)
            tasks.append(landlord_case_task)

            # Only create the judge task if we have all arguments
            if tenant_argument is not None and landlord_argument is not None:
                # Task 5: Judges evaluate
                judge_task = Task(
                    description=f"""Evaluate the arguments presented by both the Tenant's Lawyer and the Landlord's Lawyer
                    based on the user's QUESTION and the SELECTED_LAW_DETAILS that were used by the lawyers.
                    Vote on whether the tenant or landlord has the stronger legal case.
                    Consider the laws cited and the strength of their arguments.
                    Do NOT attempt to delegate legal research; make your judgment based on the provided arguments.

                    QUESTION: {question}
                    SELECTED_LAW_DETAILS: {selected_law_texts}
                    TENANT_ARGUMENT: {tenant_argument}
                    LANDLORD_ARGUMENT: {landlord_argument}

                    Vote using ONLY one of these exact string options:
                    - obviously_tenant
                    - most_likely_tenant
                    - not_sure
                    - most_likely_landlord
                    - obviously_landlord""",
                    agent=self.left_judge
                )
                logger.debug("Created judge_task: %s", judge_task)
                tasks.append(judge_task)

        return tasks

    def _get_law_texts_from_titles(self, law_titles: list):
        logger.info("Getting law texts for titles: %s", law_titles)
        law_details = {}
        if not isinstance(law_titles, list):
            logger.warning("law_titles is not a list: %s", law_titles)
            return law_details

        for title in law_titles:
            found = False
            for key, law_data in LAWS_DATABASE.items():
                if law_data.get("title", "").lower() == title.lower() or key.lower() == title.lower().replace(" ", "_"):
                    law_details[title] = law_data
                    found = True
                    break
            if not found:
                logger.warning("Law title '%s' not found in LAWS_DATABASE", title)
                law_details[title] = "Content not found in database."
        logger.debug("Retrieved law details: %s", law_details)
        return law_details

    def process_question(self, question):
        logger.info("Processing question: %s", question)
        tasks = self.create_tasks(question)
        voting_tracker = VotingTracker()

        logger.info("Starting easy answer check")
        easy_answer_crew = Crew(
            agents=[self.easy_answer_agent],
            tasks=[tasks[0]],
            verbose=2,
            process=Process.sequential
        )
        easy_answer_result = easy_answer_crew.kickoff()
        logger.info("Easy answer result: %s", easy_answer_result)

        # Only return early if we got a real answer (not NEEDS_EXPERT)
        if easy_answer_result and easy_answer_result.strip().upper() != 'NEEDS_EXPERT' and not easy_answer_result.startswith("NEEDS_EXPERT"):
            logger.info("Found direct answer in overview")
            return easy_answer_result

        logger.info("No direct answer found, proceeding with law selection")
        law_selection_crew = Crew(
            agents=[self.law_selector],
            tasks=[tasks[1]],
            verbose=2,
            process=Process.sequential
        )
        selected_laws_titles = law_selection_crew.kickoff()
        logger.info("Selected laws titles: %s", selected_laws_titles)

        # Parse the law titles from the string output
        if isinstance(selected_laws_titles, str):
            try:
                # Remove any leading/trailing whitespace and brackets
                cleaned_str = selected_laws_titles.strip('[] ')
                # Split by comma and clean each title
                selected_laws_titles = [title.strip() for title in cleaned_str.split(',')]
                logger.info("Parsed law titles: %s", selected_laws_titles)
            except Exception as e:
                logger.error("Error parsing Law Selector output: %s. Output: %s", e, selected_laws_titles)
                return "Error: Could not parse law titles from Law Selector."

        if not isinstance(selected_laws_titles, list):
            logger.error("Law Selector did not return a list of titles: %s", selected_laws_titles)
            return "Error: Law Selector did not return a valid list of law titles."

        selected_law_texts = self._get_law_texts_from_titles(selected_laws_titles)
        if not selected_law_texts:
            logger.warning("No law texts could be retrieved for the selected titles")
            return "Error: Could not retrieve law texts for analysis."

        # Create new tasks with the selected law texts
        tasks = self.create_tasks(question, selected_law_texts=selected_law_texts)

        logger.info("Starting tenant lawyer analysis")
        tenant_crew = Crew(agents=[self.tenant_lawyer], tasks=[tasks[2]], verbose=2, process=Process.sequential)
        tenant_argument = tenant_crew.kickoff()
        logger.info("Tenant argument received")
        voting_tracker.add_argument(f"Tenant's Initial Argument: {tenant_argument}")

        logger.info("Starting landlord lawyer analysis")
        landlord_crew = Crew(agents=[self.landlord_lawyer], tasks=[tasks[3]], verbose=2, process=Process.sequential)
        landlord_argument = landlord_crew.kickoff()
        logger.info("Landlord argument received")
        voting_tracker.add_argument(f"Landlord's Initial Argument: {landlord_argument}")

        # Create final task with all arguments
        final_task = Task(
            description=f"""Evaluate the arguments presented by both the Tenant's Lawyer and the Landlord's Lawyer
            based on the user's QUESTION and the SELECTED_LAW_DETAILS that were used by the lawyers.
            Vote on whether the tenant or landlord has the stronger legal case.
            Consider the laws cited and the strength of their arguments.
            Do NOT attempt to delegate legal research; make your judgment based on the provided arguments.

            QUESTION: {question}
            SELECTED_LAW_DETAILS: {selected_law_texts}
            TENANT_ARGUMENT: {tenant_argument}
            LANDLORD_ARGUMENT: {landlord_argument}

            Vote using ONLY one of these exact string options:
            - obviously_tenant
            - most_likely_tenant
            - not_sure
            - most_likely_landlord
            - obviously_landlord""",
            agent=self.left_judge
        )

        logger.info("Starting judge voting")
        # First round voting
        for judge_agent in [self.left_judge, self.right_judge, self.centrist_judge]:
            judge_crew = Crew(
                agents=[judge_agent],
                tasks=[final_task],
                verbose=2,
                process=Process.sequential
            )
            vote_result = judge_crew.kickoff()
            logger.info("Judge %s vote: %s", judge_agent.role, vote_result)
            try:
                vote_enum_member = Vote(vote_result.strip().lower())
                voting_tracker.record_vote(judge_agent.role, vote_enum_member)
            except ValueError:
                logger.warning("Judge %s returned an invalid vote: '%s'. Recording as 'not_sure'.", judge_agent.role, vote_result)
                voting_tracker.record_vote(judge_agent.role, Vote.NOT_SURE)

        decision = voting_tracker.check_decision_criteria()
        if decision:
            logger.info("Decision reached in favor of %s", decision)
            return f"Decision reached in favor of {decision} after first round.\n{voting_tracker.get_vote_summary()}\nArguments:\n{voting_tracker.get_all_arguments_formatted()}"
        else:
            logger.info("No clear decision reached")
            return f"Could not reach a clear decision after first round.\n{voting_tracker.get_vote_summary()}\nArguments:\n{voting_tracker.get_all_arguments_formatted()}" 