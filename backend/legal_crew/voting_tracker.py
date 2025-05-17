from enum import Enum
from typing import List, Dict, Optional

class Vote(Enum):
    OBVIOUSLY_TENANT = "obviously_tenant"
    MOST_LIKELY_TENANT = "most_likely_tenant"
    NOT_SURE = "not_sure"
    MOST_LIKELY_LANDLORD = "most_likely_landlord"
    OBVIOUSLY_LANDLORD = "obviously_landlord"

class VotingTracker:
    def __init__(self):
        self.rounds: List[Dict[str, Vote]] = []
        self.current_round = 0
        self.decision: Optional[str] = None
        self.arguments: List[str] = []
        
    def start_new_round(self):
        self.current_round += 1
        self.rounds.append({})
        
    def record_vote(self, judge_name: str, vote: Vote):
        if self.current_round == 0:
            self.start_new_round()
        self.rounds[self.current_round - 1][judge_name] = vote
        
    def add_argument(self, argument: str):
        self.arguments.append(argument)
        
    def get_current_round_votes(self) -> Dict[str, Vote]:
        if self.current_round == 0:
            return {}
        return self.rounds[self.current_round - 1]
    
    def check_decision_criteria(self) -> Optional[str]:
        if self.current_round == 0:
            return None
            
        current_votes = self.get_current_round_votes()
        if not current_votes:
            return None
            
        # Count votes
        tenant_obvious = sum(1 for v in current_votes.values() if v == Vote.OBVIOUSLY_TENANT)
        tenant_likely = sum(1 for v in current_votes.values() if v == Vote.MOST_LIKELY_TENANT)
        landlord_obvious = sum(1 for v in current_votes.values() if v == Vote.OBVIOUSLY_LANDLORD)
        landlord_likely = sum(1 for v in current_votes.values() if v == Vote.MOST_LIKELY_LANDLORD)
        
        # First round criteria
        if self.current_round == 1:
            if (tenant_obvious >= 3) or (tenant_obvious >= 2 and tenant_likely >= 1):
                return "tenant"
            if (landlord_obvious >= 3) or (landlord_obvious >= 2 and landlord_likely >= 1):
                return "landlord"
                
        # Second and third round criteria
        if self.current_round >= 2:
            tenant_total = tenant_obvious + tenant_likely
            landlord_total = landlord_obvious + landlord_likely
            
            if tenant_total >= 2:
                return "tenant"
            if landlord_total >= 2:
                return "landlord"
                
        return None
        
    def get_vote_summary(self) -> str:
        if not self.rounds or self.current_round == 0: # check if rounds list is empty
            return "No votes recorded yet for any completed round."

        # Summarize votes for the most recently completed round
        last_completed_round_idx = self.current_round -1
        if last_completed_round_idx < 0 or last_completed_round_idx >= len(self.rounds):
             return "No votes available to summarize yet."

        summary = f"--- Round {self.current_round} Voting Results ---\n" # current_round is correct here as it's the round being reported
        current_round_votes = self.rounds[last_completed_round_idx] # Votes for the round that just finished
        if not current_round_votes:
            summary += "No votes were cast in this round.\n"
        else:
            for judge, vote in current_round_votes.items():
                summary += f"{judge}: {vote.value}\n"
        return summary
        
    def get_all_arguments(self) -> List[str]:
        return self.arguments

    def get_all_arguments_formatted(self) -> str:
        return "\n\n".join(self.arguments) 