from crewai import Agent, Task, Crew, Process, LLM
from crewai.tools import tool
from google import genai
import os
from dotenv import load_dotenv
from pathlib import Path
from .laws_database import LAWS_DATABASE
from .voting_tracker import VotingTracker, Vote
from .cpi_tool import CPITool
from .cao_tool import CAOTool
from .percentage_calculator import PercentageCalculator
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
gemini_llm = LLM(
    model="gemini/gemini-1.5-flash",
    api_key=os.getenv('GEMINI_API_KEY'),
    temperature=0.7
)

# Load overview.txt
overview_path = Path(__file__).parent.parent / 'laws' / 'overview.txt'
with open(overview_path, 'r', encoding='utf-8') as f:
    OVERVIEW_TEXT = f.read()

class LegalCrew:
    def __init__(self):
        logger.info("Initializing LegalCrew")
        self.cpi_tool = CPITool()
        self.cao_tool = CAOTool()
        self.percentage_calculator = PercentageCalculator()
        
        # Create percentage calculator tools using the @tool decorator
        @tool("Calculate Percentage Change")
        def calculate_percentage_change(original_amount: float, new_amount: float) -> float:
            """Calculate the percentage change between two amounts."""
            return self.percentage_calculator.calculate_percentage_change(original_amount, new_amount)

        @tool("Check if Increase is Legal")
        def is_increase_legal(percentage_change: float, legal_limit: float) -> bool:
            """Check if a percentage increase is within legal limits."""
            return self.percentage_calculator.is_increase_legal(percentage_change, legal_limit)

        @tool("Calculate New Amount")
        def calculate_new_amount(original_amount: float, percentage_change: float) -> float:
            """Calculate the new amount based on a percentage change."""
            return self.percentage_calculator.calculate_new_amount(original_amount, percentage_change)

        # Initialize the contract analyzer
        self.contract_analyzer = Agent(
            role='Contract Analyzer',
            goal='Analyze rental contracts and identify potentially illegal clauses',
            backstory="""You are an expert in rental contract analysis with deep knowledge of 
            housing laws and regulations. You excel at identifying clauses that may violate 
            tenant rights or exceed legal limits. You can spot both obvious and subtle 
            violations of rental laws.""",
            verbose=True,
            allow_delegation=False,
            llm=gemini_llm
        )

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

        # Initialize the rent increase analyst
        self.rent_analyst = Agent(
            role='Rent Increase Analyst',
            goal='Analyze rent increases and determine their legality',
            backstory="""You are an expert in rent control and housing law, specializing in 
            analyzing rent increases. You understand the legal framework for rent increases 
            and can determine if a rent increase is legal based on the applicable laws and 
            current economic indicators. You know when to use CPI vs CAO wage index based on 
            the type of rental agreement and relevant laws. You can calculate percentage changes 
            and validate them against legal limits.""",
            verbose=True,
            allow_delegation=False,
            llm=gemini_llm,
            tools=[calculate_percentage_change, is_increase_legal, calculate_new_amount]
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

    def create_tasks(self, question, selected_law_texts=None, tenant_argument=None, landlord_argument=None, contract_text=None):
        logger.info("Creating tasks for question: %s", question)
        
        tasks = []

        # If we have a contract, analyze it first
        if contract_text:
            contract_analysis_task = Task(
                description=f"""Analyze the rental contract and identify any potentially illegal clauses.
                Focus on:
                1. Rent increase clauses and their compliance with current laws
                2. Service cost provisions
                3. Maintenance and repair obligations
                4. Termination conditions
                5. Any other clauses that might violate tenant rights
                
                CONTRACT_TEXT: {contract_text}
                SELECTED_LAW_DETAILS: {selected_law_texts}
                
                For each potentially illegal clause:
                1. Quote the exact clause
                2. Explain why it might be illegal
                3. Cite the relevant law
                4. Suggest how it should be modified to be legal
                
                If you need more context about the rental situation, include "MORE_CONTEXT_NEEDED" in your response.""",
                expected_output="A detailed analysis of the rental contract, identifying any illegal clauses with explanations and suggested modifications.",
                agent=self.contract_analyzer
            )
            logger.debug("Created contract_analysis_task: %s", contract_analysis_task)
            tasks.append(contract_analysis_task)

        # Task 1: Easy Answer
        easy_answer_task = Task(
            description=f"""Check if the following question can be answered directly from the
            overview document. If you find a clear answer, return it. If not, return 'NEEDS_EXPERT'.
            For rent increase questions, always return 'NEEDS_EXPERT' as we need to analyze the specific increase.
            Question: {question}
            Overview Document: {OVERVIEW_TEXT}""",
            expected_output="Either a direct answer from the overview document or 'NEEDS_EXPERT' if deeper analysis is required.",
            agent=self.easy_answer_agent
        )
        logger.debug("Created easy_answer_task: %s", easy_answer_task)
        tasks.append(easy_answer_task)

        # Task 2: Select relevant laws
        law_selection_task = Task(
            description=f"""Analyze the question and select the most relevant laws from the
            AVAILABLE LAWS list. Return a list of the most relevant law titles, ordered by relevance.
            Ensure the output is ONLY a list of strings (law titles).
            For rent increase questions, make sure to include 'Huurprijzenwet woonruimte' and 'Burgerlijk Wetboek Boek 5'.
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
            expected_output="A list of relevant law titles, ordered by relevance to the question.",
            agent=self.law_selector
        )
        logger.debug("Created law_selection_task: %s", law_selection_task)
        tasks.append(law_selection_task)

        # Only create these tasks if we have the required data
        if selected_law_texts is not None:
            # Task 3: Rent Increase Analysis
            rent_analysis_task = Task(
                description=f"""Analyze the rent increase situation based on the provided information.
                You have access to:
                1. Economic indicators:
                   - CPI: {self.cpi_tool.get_current_cpi()}%
                   - CAO wage index: {self.cao_tool.get_current_cao_index()}%
                2. Percentage calculator tools:
                   - calculate_percentage_change(original_amount: float, new_amount: float) -> float
                   - is_increase_legal(percentage_change: float, legal_limit: float) -> bool
                   - calculate_new_amount(original_amount: float, percentage_change: float) -> float
                
                QUESTION: {question}
                SELECTED_LAW_DETAILS: {selected_law_texts}
                
                Based on the law text:
                1. Determine which economic indicator (CPI or CAO) applies to this situation
                2. Extract the current rent and proposed rent from the question if provided
                3. Use calculate_percentage_change to determine the actual percentage increase
                4. Use is_increase_legal to check if the increase is within legal limits
                5. Explain what the tenant can do if it's not legal
                6. Note any relevant deadlines or procedures
                
                If you need the actual rent increase amount or current rent, include "CONTRACT_NEEDED" in your response.
                If you can determine the increase is illegal based on the proposed percentage, state this clearly.
                
                Remember: Different types of rental agreements may use different economic indicators.
                Use the law text to determine which indicator applies in this case.""",
                expected_output="A detailed analysis of the rent increase legality, including calculations, legal basis, and recommended actions.",
                agent=self.rent_analyst
            )
            logger.debug("Created rent_analysis_task: %s", rent_analysis_task)
            tasks.append(rent_analysis_task)

            # Task 4: Tenant's lawyer presents case
            tenant_case_task = Task(
                description=f"""Based on the user's QUESTION and the provided SELECTED_LAW_DETAILS,
                present a compelling argument from the tenant's perspective.
                Focus on how these laws protect tenant rights in this situation.
                Do NOT attempt to delegate this task or ask other agents for the law text; use the provided SELECTED_LAW_DETAILS.

                QUESTION: {question}
                SELECTED_LAW_DETAILS: {selected_law_texts}""",
                expected_output="A comprehensive legal argument from the tenant's perspective, citing relevant laws and precedents.",
                agent=self.tenant_lawyer
            )
            logger.debug("Created tenant_case_task: %s", tenant_case_task)
            tasks.append(tenant_case_task)

            # Task 5: Landlord's lawyer presents case
            landlord_case_task = Task(
                description=f"""Based on the user's QUESTION and the provided SELECTED_LAW_DETAILS,
                present a compelling argument from the landlord's perspective.
                Focus on how these laws protect landlord rights in this situation.
                Do NOT attempt to delegate this task or ask other agents for the law text; use the provided SELECTED_LAW_DETAILS.

                QUESTION: {question}
                SELECTED_LAW_DETAILS: {selected_law_texts}""",
                expected_output="A comprehensive legal argument from the landlord's perspective, citing relevant laws and precedents.",
                agent=self.landlord_lawyer
            )
            logger.debug("Created landlord_case_task: %s", landlord_case_task)
            tasks.append(landlord_case_task)

            # Only create the judge task if we have all arguments
            if tenant_argument is not None and landlord_argument is not None:
                # Task 6: Judges evaluate
                judge_task = Task(
                    description=f"""Evaluate the arguments presented by both the Tenant's Lawyer and the Landlord's Lawyer
                    based on the user's QUESTION and the SELECTED_LAW_DETAILS that were used by the lawyers.
                    Vote on whether the tenant or landlord has the stronger legal case.
                    Consider the laws cited and the strength of their arguments.
                    Do NOT attempt to delegate legal research; make your judgment based on the provided arguments.
                    If you determine that the rental contract is necessary to make a proper judgment, include the text "CONTRACT_NEEDED" in your response.

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
                    expected_output="A clear vote on the case with justification based on the presented arguments and laws.",
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

    def process_question(self, question, contract_text=None):
        logger.info("Processing question: %s", question)
        tasks = self.create_tasks(question, contract_text=contract_text)
        voting_tracker = VotingTracker()

        # If we have a contract, analyze it first
        if contract_text:
            logger.info("Starting contract analysis")
            contract_analysis_crew = Crew(
                agents=[self.contract_analyzer],
                tasks=[tasks[0]],  # Contract analysis is the first task
                verbose=True,
                process=Process.sequential
            )
            contract_analysis_result = contract_analysis_crew.kickoff()
            logger.info("Contract analysis result: %s", contract_analysis_result)
            
            # Check if we need more context
            if "MORE_CONTEXT_NEEDED" in contract_analysis_result.upper():
                return f"To properly analyze your contract, we need more information about your rental situation. Please provide additional context about your rental agreement and any specific concerns you have. MORE_CONTEXT_NEEDED"
            
            # If we have a clear analysis, return it
            return contract_analysis_result

        logger.info("Starting easy answer check")
        easy_answer_crew = Crew(
            agents=[self.easy_answer_agent],
            tasks=[tasks[0]],  # Easy answer is now the first task if no contract
            verbose=True,
            process=Process.sequential
        )
        easy_answer_result = easy_answer_crew.kickoff()
        logger.info("Easy answer result: %s", easy_answer_result)

        # Only return early if we got a real answer (not NEEDS_EXPERT)
        if easy_answer_result and str(easy_answer_result).strip().upper() != 'NEEDS_EXPERT' and not str(easy_answer_result).startswith("NEEDS_EXPERT"):
            logger.info("Found direct answer in overview")
            return str(easy_answer_result)

        logger.info("No direct answer found, proceeding with law selection")
        law_selection_crew = Crew(
            agents=[self.law_selector],
            tasks=[tasks[1]],  # Law selection is now the second task
            verbose=True,
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

        # Convert CrewOutput to string and parse if needed
        if not isinstance(selected_laws_titles, list):
            try:
                # Convert CrewOutput to string and parse
                law_titles_str = str(selected_laws_titles)
                # Remove any leading/trailing whitespace and brackets
                cleaned_str = law_titles_str.strip('[] ')
                # Split by comma and clean each title
                selected_laws_titles = [title.strip() for title in cleaned_str.split(',')]
                logger.info("Parsed law titles from CrewOutput: %s", selected_laws_titles)
            except Exception as e:
                logger.error("Error parsing Law Selector CrewOutput: %s. Output: %s", e, selected_laws_titles)
                return "Error: Could not parse law titles from Law Selector."

        if not selected_laws_titles:
            logger.warning("No law titles could be parsed from the Law Selector output")
            return "Error: Could not retrieve law titles for analysis."

        selected_law_texts = self._get_law_texts_from_titles(selected_laws_titles)
        if not selected_law_texts:
            logger.warning("No law texts could be retrieved for the selected titles")
            return "Error: Could not retrieve law texts for analysis."

        # Create new tasks with the selected law texts
        tasks = self.create_tasks(question, selected_law_texts=selected_law_texts)

        # If this is a rent increase question, run the rent analysis first
        if "rent increase" in question.lower() or "rent hike" in question.lower():
            logger.info("Starting rent increase analysis")
            rent_analysis_crew = Crew(
                agents=[self.rent_analyst],
                tasks=[tasks[2]],  # Rent analysis task is now the third task
                verbose=True,
                process=Process.sequential
            )
            rent_analysis_result = rent_analysis_crew.kickoff()
            logger.info("Rent analysis result: %s", rent_analysis_result)
            
            # Convert CrewOutput to string for checking
            rent_analysis_str = str(rent_analysis_result)
            
            # Check if we need the contract
            if "CONTRACT_NEEDED" in rent_analysis_str.upper():
                logger.info("Rent analyst indicates contract is needed")
                return f"To properly evaluate your rent increase, we need to see your rental contract and the proposed increase amount. This will help us determine if the increase is legal. CONTRACT_NEEDED"
            
            # If we have a clear answer from the rent analysis, return it
            if "legal" in rent_analysis_str.lower() or "illegal" in rent_analysis_str.lower():
                return rent_analysis_str

        logger.info("Starting tenant lawyer analysis")
        tenant_crew = Crew(agents=[self.tenant_lawyer], tasks=[tasks[3]], verbose=True, process=Process.sequential)
        tenant_argument = tenant_crew.kickoff()
        logger.info("Tenant argument received")
        voting_tracker.add_argument(f"Tenant's Initial Argument: {tenant_argument}")

        # Check if tenant lawyer indicates we need the contract
        if "CONTRACT_NEEDED" in tenant_argument.upper():
            logger.info("Tenant lawyer indicates contract is needed")
            return f"To properly evaluate your case, we need to see your rental contract. This will help us understand the specific terms and conditions that apply to your situation. CONTRACT_NEEDED"

        logger.info("Starting landlord lawyer analysis")
        landlord_crew = Crew(agents=[self.landlord_lawyer], tasks=[tasks[4]], verbose=True, process=Process.sequential)
        landlord_argument = landlord_crew.kickoff()
        logger.info("Landlord argument received")
        voting_tracker.add_argument(f"Landlord's Initial Argument: {landlord_argument}")

        # Check if landlord lawyer indicates we need the contract
        if "CONTRACT_NEEDED" in landlord_argument.upper():
            logger.info("Landlord lawyer indicates contract is needed")
            return f"To properly evaluate your case, we need to see your rental contract. This will help us understand the specific terms and conditions that apply to your situation. CONTRACT_NEEDED"

        # Create final task with all arguments
        final_task = Task(
            description=f"""Evaluate the arguments presented by both the Tenant's Lawyer and the Landlord's Lawyer
            based on the user's QUESTION and the SELECTED_LAW_DETAILS that were used by the lawyers.
            Vote on whether the tenant or landlord has the stronger legal case.
            Consider the laws cited and the strength of their arguments.
            Do NOT attempt to delegate legal research; make your judgment based on the provided arguments.
            If you determine that the rental contract is necessary to make a proper judgment, include the text "CONTRACT_NEEDED" in your response.

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
            expected_output="A clear vote on the case with justification based on the presented arguments and laws.",
            agent=self.left_judge
        )

        logger.info("Starting judge voting")
        # First round voting
        for judge_agent in [self.left_judge, self.right_judge, self.centrist_judge]:
            judge_crew = Crew(
                agents=[judge_agent],
                tasks=[final_task],
                verbose=True,
                process=Process.sequential
            )
            vote_result = judge_crew.kickoff()
            logger.info("Judge %s vote: %s", judge_agent.role, vote_result)
            
            # Check if judge indicates we need the contract
            if "CONTRACT_NEEDED" in vote_result.upper():
                logger.info("Judge indicates contract is needed")
                return f"To properly evaluate your case, we need to see your rental contract. This will help us understand the specific terms and conditions that apply to your situation. CONTRACT_NEEDED"
            
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