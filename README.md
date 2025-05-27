# Legal AI App - Second Place Hackathon Winner 🏆

## Hackathon: Driving the Next Evolution in LegalTech for an Open Economy
Sponsored by Scroll | Amsterdam, The Netherlands | May 16-18, 2025

## Team
- **Paul Spencer** - Backend/AI Architecture
- **Bob McLaughlin** - Frontend Development
- **Tony Barratt** - Project Management
- **Melody Ma** - Legal Subject Matter Expert

## Project Overview

Legal AI App is an innovative solution addressing the complexity and inaccessibility of Dutch rental law. Our platform provides real-time legal guidance for both tenants and landlords, democratizing access to legal information and reducing the power imbalance in rental relationships.

![Legal AI App Interface](legal-ai-app.png)

### The Problem
- Dutch rental law is complex, opaque, and often inaccessible
- Tenants & landlords frequently misinterpret their rights and obligations
- Legal help is expensive and slow
- Power imbalance leads to lost money, time, and trust

### The Opportunity
- 3+ million tenants in the Netherlands
- 500k+ private landlords and housing providers
- 80% of disputes involve routine, solvable issues
- No existing real-time legal triage platform for both parties

### Core Use Cases

#### Tenants (Proof of Concept)
- Is my rent increase legal?
- Can I break my lease early?
- Can my landlord withhold my deposit?
- What should I do about repairs?



## Technical Architecture

### AI System Design
Our solution implements a sophisticated hierarchical multi-agent system using crewAI, demonstrating advanced AI orchestration and decision-making capabilities.

#### Agent Architecture
1. **Gatekeeper Agent**
   - Initial query classification
   - Route to appropriate specialist agents

2. **Simple Rule Agent**
   - Quick answers for common scenarios
   - Direct reference to legal statutes

3. **Law Selector**
   - Identifies relevant legal frameworks
   - Prioritizes applicable laws

4. **Rent Expert**
   - Specialized in rent control analysis
   - Integrates with economic indicators

5. **Legal Advocates**
   - Tenant's Lawyer: Argues tenant rights
   - Landlord's Lawyer: Argues landlord rights

6. **Judicial Panel**
   - Three-judge system for balanced decisions
   - Implements weighted voting mechanism

#### Specialized Tools
- CPI/CAO Data Integration
- Percentage Calculator
- Legal Statute Database
- Voting System

#### Decision Process
1. Initial query analysis
2. Law selection and interpretation
3. Dual-perspective legal analysis
4. Three-round voting system
5. Majority-based outcome determination

## Tech Stack
- **Frontend:** PHP, HTML, CSS, JavaScript
- **Backend:** Python (FastAPI)
- **AI Framework:** crewAI
- **Version Control:** Git + GitHub

## Project Structure
```
legal-ai-app/
├── frontend-php/
│   ├── index.php
│   ├── css/
│   └── js/
├── backend-python/
│   ├── app/
│   │   ├── main.py
│   │   └── ai_logic.py
│   └── requirements.txt
├── .env.example
└── README.md
```

## Getting Started

### Backend Setup

1. Clone the repository
```bash
git clone https://github.com/avalon109/legal-ai-app.git
cd legal-ai-app
```

2. Set up Python environment
```bash
cd backend
python3 -m venv venv
source venv/bin/activate  # On Windows use: .\venv\Scripts\activate
pip install --upgrade pip  # Ensure pip is up to date
pip install -r requirements.txt
```

3. Configure Environment Variables
Create a `.env` file in the backend directory with:
```bash
GEMINI_API_KEY=your_gemini_api_key_here
```

#### Required Secrets

The application requires the following secrets to function:

1. **GEMINI_API_KEY**
   - Required for: AI model access (Gemini 1.5 Flash) or higher
   - Used by: The multi-agent system for legal analysis
   - Must be set in:
     - Local development: `.env` file
     - Production: GitHub Actions secrets

2. **AWS Deployment Secrets** (for production deployment)
   - `AWS_ACCESS_KEY_ID`: AWS IAM access key for deployment
   - `AWS_SECRET_ACCESS_KEY`: AWS IAM secret key for deployment
   - `AWS_REGION`: AWS region where EC2 instance is hosted
   - `EC2_HOST`: Public IP or DNS of the EC2 instance
   - `EC2_USERNAME`: SSH username for EC2 (typically 'ec2-user')
   - `EC2_SSH_KEY`: Private SSH key for EC2 access

The deployment workflow uses these secrets to:
- Authenticate with AWS services
- Deploy to EC2 instance
- Set up the Python environment
- Configure the systemd service
- Manage application logs

4. Start the backend server
```bash
python main.py
```

### Frontend Setup
```bash
php -S localhost:8000 -t frontend-php
```

