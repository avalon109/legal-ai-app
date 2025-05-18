"""
Mock database of laws for testing the legal AI system.
In a real system, this would be replaced with a proper database or API.
"""

LAWS_DATABASE = {
    "contract_law": {
        "title": "Contract Law",
        "sections": [
            {
                "id": "CL-101",
                "title": "Formation of Contracts",
                "text": "A contract is formed when there is an offer, acceptance, and consideration between parties.",
                "case_law": [
                    {
                        "case": "Smith v. Jones (2020)",
                        "summary": "Established that email communication can constitute a valid offer."
                    }
                ]
            },
            {
                "id": "CL-102",
                "title": "Breach of Contract",
                "text": "A breach occurs when one party fails to fulfill their obligations under the contract.",
                "case_law": [
                    {
                        "case": "Brown v. Wilson (2019)",
                        "summary": "Defined the standard for material breach in commercial contracts."
                    }
                ]
            }
        ]
    },
    "property_law": {
        "title": "Property Law",
        "sections": [
            {
                "id": "PL-101",
                "title": "Real Property Rights",
                "text": "Real property rights include the right to use, possess, and transfer land and buildings.",
                "case_law": [
                    {
                        "case": "Estate of Black v. White (2021)",
                        "summary": "Clarified the scope of property rights in shared spaces."
                    }
                ]
            }
        ]
    },
    "tort_law": {
        "title": "Tort Law",
        "sections": [
            {
                "id": "TL-101",
                "title": "Negligence",
                "text": "Negligence occurs when someone fails to exercise reasonable care, causing harm to another.",
                "case_law": [
                    {
                        "case": "Green v. Blue Corp (2018)",
                        "summary": "Established the modern standard for reasonable care in workplace safety."
                    }
                ]
            }
        ]
    }
}