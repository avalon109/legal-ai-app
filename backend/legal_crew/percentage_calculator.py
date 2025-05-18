import logging

logger = logging.getLogger(__name__)

class PercentageCalculator:
    def __init__(self):
        logger.info("Initializing Percentage Calculator")
        
    def calculate_percentage_change(self, original_amount: float, new_amount: float) -> float:
        """
        Calculate the percentage change between two amounts.
        Args:
            original_amount: The original amount
            new_amount: The new amount
        Returns:
            The percentage change (positive for increase, negative for decrease)
        """
        if original_amount <= 0:
            raise ValueError("Original amount must be greater than 0")
            
        percentage_change = ((new_amount - original_amount) / original_amount) * 100
        logger.info(f"Calculated percentage change: {percentage_change}% (from {original_amount} to {new_amount})")
        return percentage_change
        
    def is_increase_legal(self, percentage_change: float, legal_limit: float) -> bool:
        """
        Check if a percentage increase is within legal limits.
        Args:
            percentage_change: The calculated percentage change
            legal_limit: The maximum allowed percentage increase
        Returns:
            True if the increase is legal, False otherwise
        """
        is_legal = percentage_change <= legal_limit
        logger.info(f"Checking if {percentage_change}% increase is legal (limit: {legal_limit}%): {is_legal}")
        return is_legal
        
    def calculate_new_amount(self, original_amount: float, percentage_change: float) -> float:
        """
        Calculate the new amount based on a percentage change.
        Args:
            original_amount: The original amount
            percentage_change: The percentage change to apply
        Returns:
            The new amount after applying the percentage change
        """
        if original_amount <= 0:
            raise ValueError("Original amount must be greater than 0")
            
        new_amount = original_amount * (1 + (percentage_change / 100))
        logger.info(f"Calculated new amount: {new_amount} (from {original_amount} with {percentage_change}% change)")
        return new_amount 