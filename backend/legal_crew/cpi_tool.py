import logging

logger = logging.getLogger(__name__)

class CPITool:
    def __init__(self):
        logger.info("Initializing CPI Tool")
        
    def get_current_cpi(self) -> float:
        """
        Get the current CPI (Consumer Price Index) percentage.
        For now, returns a fixed 2% as requested.
        In the future, this could be replaced with a real API call to get actual CPI data.
        """
        logger.info("Getting current CPI")
        # TODO: Replace with actual CPI API call
        return 2.0  # Fixed 2% for now
        
    def calculate_legal_increase(self, base_percentage: float = 1.0) -> float:
        """
        Calculate the legal rent increase percentage based on CPI.
        Args:
            base_percentage: The base percentage to add to CPI (default 1.0%)
        Returns:
            The total legal increase percentage
        """
        cpi = self.get_current_cpi()
        total_increase = cpi + base_percentage
        logger.info(f"Calculated legal increase: CPI ({cpi}%) + base ({base_percentage}%) = {total_increase}%")
        return total_increase 