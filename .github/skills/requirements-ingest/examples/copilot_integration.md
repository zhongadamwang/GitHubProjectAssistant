# Using Requirements Ingest Skill in GitHub Copilot

## Direct Copilot Usage (No API Keys Required)

### Method 1: Copilot Chat Integration

Simply use this prompt pattern in Copilot Chat:

```
@workspace Please use the requirements-ingest skill to process this document:

# E-commerce Platform Requirements

## User Authentication
- The system shall authenticate users within 3 seconds using OAuth2
- Users must be able to login with Google, Facebook, Apple accounts  
- Multi-factor authentication is required for admin users
- Password reset emails shall be sent within 30 seconds

## Performance Requirements
- Product search must return results within 200ms
- The system shall support 10,000 concurrent users
- Database queries must complete under 100ms for 95% of requests
- Image loading should not exceed 2 seconds

Project ID: ECOM-2024

Please extract atomic requirements following the requirements-ingest methodology and return structured JSON with classifications and traceability.
```

### Method 2: Step-by-Step Processing

**Step 1:** Ask Copilot to analyze the document structure
```
Analyze this requirements document and identify the main sections and requirement types present.
```

**Step 2:** Request structured extraction  
```
Extract each requirement as an atomic statement with:
- Unique ID (R-001, R-002, etc.)
- Source section and location
- Classification (functional/nonfunctional/constraint/assumption/out-of-scope)
- Confidence score 0.0-1.0
```

**Step 3:** Get glossary terms
```
Identify domain-specific terms and acronyms that appear multiple times in this document for a glossary.
```

## Example Copilot Conversation

**You:**
```
@workspace I have this requirements document for a banking system. Please use the requirements-ingest approach to extract and classify all requirements:

BANKING SYSTEM REQUIREMENTS

Account Management:
The system shall allow customers to create checking and savings accounts online.
Account holders must view transaction history for 24 months.
Automated bill pay must support recurring schedules.

Security Requirements:
All transactions must use TLS 1.3 encryption minimum.
Multi-factor authentication is required for all logins.
Transaction amounts over $10,000 require additional verification.

Performance:
System must handle 50,000 concurrent users during peak hours.
Transaction processing cannot exceed 3 seconds.

Project: BANK-SYS-2024
```

**Copilot Response:**
```json
{
  "project_id": "BANK-SYS-2024",
  "requirements": [
    {
      "id": "R-001",
      "source_file": "requirements_document",
      "location_hint": "Account Management section, requirement 1",
      "text": "The system shall allow customers to create checking and savings accounts online",
      "tags": ["functional"],
      "confidence": 0.95
    },
    {
      "id": "R-002", 
      "source_file": "requirements_document",
      "location_hint": "Account Management section, requirement 2",
      "text": "Account holders must view transaction history for 24 months",
      "tags": ["functional"],
      "confidence": 0.90
    },
    {
      "id": "R-003",
      "source_file": "requirements_document", 
      "location_hint": "Security Requirements section, requirement 1",
      "text": "All transactions must use TLS 1.3 encryption minimum",
      "tags": ["nonfunctional", "constraint"],
      "confidence": 0.95
    },
    {
      "id": "R-004",
      "source_file": "requirements_document",
      "location_hint": "Performance section, requirement 1", 
      "text": "System must handle 50,000 concurrent users during peak hours",
      "tags": ["nonfunctional"],
      "confidence": 0.88
    }
  ],
  "glossary_suspects": ["TLS", "MFA", "API", "transaction", "authentication"]
}
```

## Advanced Copilot Integration

### Using with File Attachments
```
@workspace I've uploaded a PDF requirements document. Please:

1. Extract all requirements using the requirements-ingest methodology
2. Classify each as functional/nonfunctional/constraint/assumption/out-of-scope
3. Provide traceability to page numbers and sections
4. Generate confidence scores based on requirement clarity
5. Identify glossary terms for domain vocabulary

Project ID: PDF-ANALYSIS-001
Format as JSON per the requirements-ingest schema.
```

### Iterative Refinement
```
The previous extraction looks good, but please:
- Split requirement R-005 into two atomic requirements
- Increase confidence score for R-003 as it's very specific  
- Add location hints that include page numbers
- Check if "API gateway" should be in glossary_suspects
```

## Benefits of Copilot Integration

✅ **No Setup Required**: Works immediately without API key configuration  
✅ **Context Aware**: Understands your project and codebase context  
✅ **Interactive**: Can refine and improve results through conversation  
✅ **Integrated**: Seamlessly works within your existing development workflow  
✅ **Cost Effective**: No additional API costs beyond your Copilot subscription  
✅ **Collaborative**: Can involve team members in requirement analysis  

## Tips for Best Results

1. **Be Specific**: Clearly state "use requirements-ingest methodology"
2. **Provide Context**: Include project ID and domain information  
3. **Request JSON**: Ask for structured output following the schema
4. **Iterate**: Refine results through follow-up conversations
5. **Validate**: Ask Copilot to check its own output against the schema