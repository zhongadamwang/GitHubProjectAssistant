#!/usr/bin/env python3
"""
Process W5H Analysis Skill
Analyzes requirements using Who, What, When, Where, Why, How framework
"""

import json
import os
from datetime import datetime
from pathlib import Path
from typing import Dict, List, Any


class W5HAnalyzer:
    """
    Analyzes requirements using the W5H framework to extract:
    - WHO: Stakeholders, roles, responsibilities
    - WHAT: Functional/non-functional requirements, scope  
    - WHEN: Timeline, milestones, dependencies
    - WHERE: Context, environment, integration points
    - WHY: Business drivers, success metrics, value proposition
    - HOW: Implementation approach, methodology, risk mitigation
    """
    
    def __init__(self):
        self.analysis_framework = "W5H"
        
    def analyze_requirements(self, requirements_text: str, project_id: str) -> Dict[str, Any]:
        """
        Analyze requirements text using W5H framework
        
        Args:
            requirements_text: Raw requirements document content
            project_id: Unique project identifier
            
        Returns:
            Dictionary containing W5H analysis results
        """
        
        # Create output structure
        analysis = {
            "project_id": project_id,
            "generated_at": datetime.now().isoformat(),
            "analysis_framework": self.analysis_framework,
            "source_text": requirements_text,
            "who": self._analyze_who(requirements_text),
            "what": self._analyze_what(requirements_text),
            "when": self._analyze_when(requirements_text),
            "where": self._analyze_where(requirements_text),
            "why": self._analyze_why(requirements_text),
            "how": self._analyze_how(requirements_text)
        }
        
        return analysis
    
    def _analyze_who(self, text: str) -> Dict[str, Any]:
        """Extract stakeholders, roles, and responsibilities"""
        # This is a simplified implementation
        # In practice, this would use NLP to extract stakeholder information
        return {
            "primary_stakeholders": [
                {
                    "role": "End Users", 
                    "description": "Primary users of the system",
                    "influence": "high",
                    "interest": "high"
                }
            ],
            "secondary_stakeholders": [],
            "roles_responsibilities": []
        }
    
    def _analyze_what(self, text: str) -> Dict[str, Any]:
        """Extract functional/non-functional requirements and scope"""
        return {
            "functional_requirements": [],
            "non_functional_requirements": [],
            "scope": {
                "in_scope": [],
                "out_of_scope": []
            }
        }
    
    def _analyze_when(self, text: str) -> Dict[str, Any]:
        """Extract timeline, milestones, and dependencies"""
        return {
            "timeline": {
                "total_duration": "TBD",
                "phases": []
            },
            "milestones": [],
            "dependencies": []
        }
    
    def _analyze_where(self, text: str) -> Dict[str, Any]:
        """Extract context, environment, and integration points"""
        return {
            "environment": {},
            "integration_points": {},
            "constraints": []
        }
    
    def _analyze_why(self, text: str) -> Dict[str, Any]:
        """Extract business drivers, success metrics, and rationale"""
        return {
            "business_drivers": {},
            "success_metrics": {},
            "value_proposition": ""
        }
    
    def _analyze_how(self, text: str) -> Dict[str, Any]:
        """Extract implementation approach and methodology"""
        return {
            "technical_approach": {},
            "implementation_phases": [],
            "risks": []
        }
    
    def generate_markdown_report(self, analysis: Dict[str, Any]) -> str:
        """Generate markdown format W5H analysis report"""
        
        report = f"""# W5H Requirements Analysis

**Project**: {analysis['project_id']}
**Generated**: {analysis['generated_at']}
**Analysis Framework**: {analysis['analysis_framework']}

## WHO - Stakeholders and Roles

### Primary Stakeholders
{self._format_stakeholders(analysis['who']['primary_stakeholders'])}

### Secondary Stakeholders  
{self._format_stakeholders(analysis['who']['secondary_stakeholders'])}

### Roles and Responsibilities
{self._format_roles_table(analysis['who']['roles_responsibilities'])}

## WHAT - Functional and Non-Functional Requirements

### Functional Requirements
{self._format_requirements(analysis['what']['functional_requirements'])}

### Non-Functional Requirements
{self._format_requirements(analysis['what']['non_functional_requirements'])}

### System Boundaries
- **In Scope**: {', '.join(analysis['what']['scope']['in_scope'])}
- **Out of Scope**: {', '.join(analysis['what']['scope']['out_of_scope'])}

## WHEN - Timeline and Milestones

### Project Timeline
Duration: {analysis['when']['timeline']['total_duration']}

### Critical Milestones
{self._format_milestones(analysis['when']['milestones'])}

## WHERE - Context and Environment

### Operational Environment
{self._format_environment(analysis['where']['environment'])}

### Integration Points
{self._format_integration_points(analysis['where']['integration_points'])}

## WHY - Purpose and Business Rationale

### Business Drivers
{self._format_business_drivers(analysis['why']['business_drivers'])}

### Success Metrics
{self._format_success_metrics(analysis['why']['success_metrics'])}

## HOW - Implementation Approach and Methods

### Technical Approach
{self._format_technical_approach(analysis['how']['technical_approach'])}

### Implementation Phases
{self._format_implementation_phases(analysis['how']['implementation_phases'])}

### Risk Management
{self._format_risks(analysis['how']['risks'])}

## Summary

This W5H analysis provides a comprehensive view of the project requirements across all critical dimensions for effective planning and implementation.
"""
        return report
    
    def _format_stakeholders(self, stakeholders: List[Dict]) -> str:
        if not stakeholders:
            return "- *To be identified during analysis*"
        
        result = []
        for stakeholder in stakeholders:
            result.append(f"- **{stakeholder['role']}**: {stakeholder['description']}")
        return '\n'.join(result)
    
    def _format_roles_table(self, roles: List[Dict]) -> str:
        if not roles:
            return "*Roles and responsibilities to be defined during analysis*"
            
        table = "| Role | Responsibilities | Authority Level |\n"
        table += "|------|-----------------|----------------|\n"
        for role in roles:
            responsibilities = ', '.join(role.get('responsibilities', []))
            table += f"| {role['role']} | {responsibilities} | {role.get('authority_level', 'TBD')} |\n"
        return table
    
    def _format_requirements(self, requirements: List[Dict]) -> str:
        if not requirements:
            return "- *Requirements to be extracted during analysis*"
            
        result = []
        for req in requirements:
            result.append(f"- **{req.get('id', 'REQ-XX')}**: {req.get('description', req)}")
        return '\n'.join(result)
    
    def _format_milestones(self, milestones: List[Dict]) -> str:
        if not milestones:
            return "*Milestones to be defined during analysis*"
            
        result = []
        for milestone in milestones:
            result.append(f"- **{milestone.get('name')}**: {milestone.get('target_date')}")
        return '\n'.join(result)
    
    def _format_environment(self, environment: Dict) -> str:
        if not environment:
            return "*Environment details to be defined during analysis*"
            
        result = []
        for key, value in environment.items():
            result.append(f"- **{key.title()}**: {value}")
        return '\n'.join(result)
    
    def _format_integration_points(self, integration: Dict) -> str:
        if not integration:
            return "*Integration points to be identified during analysis*"
            
        result = []
        for key, value in integration.items():
            result.append(f"- **{key.title()}**: {value}")
        return '\n'.join(result)
    
    def _format_business_drivers(self, drivers: Dict) -> str:
        if not drivers:
            return "*Business drivers to be identified during analysis*"
            
        result = []
        for key, value in drivers.items():
            result.append(f"- **{key.title()}**: {value}")
        return '\n'.join(result)
    
    def _format_success_metrics(self, metrics: Dict) -> str:
        if not metrics:
            return "*Success metrics to be defined during analysis*"
            
        result = []
        for key, value in metrics.items():
            result.append(f"- **{key.title()}**: {value}")
        return '\n'.join(result)
    
    def _format_technical_approach(self, approach: Dict) -> str:
        if not approach:
            return "*Technical approach to be defined during analysis*"
            
        result = []
        for key, value in approach.items():
            result.append(f"- **{key.title()}**: {value}")
        return '\n'.join(result)
    
    def _format_implementation_phases(self, phases: List[Dict]) -> str:
        if not phases:
            return "*Implementation phases to be defined during analysis*"
            
        result = []
        for phase in phases:
            result.append(f"- **{phase.get('phase')}**: {phase.get('description')}")
        return '\n'.join(result)
    
    def _format_risks(self, risks: List[Dict]) -> str:
        if not risks:
            return "*Risks to be identified during analysis*"
            
        result = []
        for risk in risks:
            result.append(f"- **Risk**: {risk.get('risk')}")
            result.append(f"  - **Mitigation**: {risk.get('mitigation')}")
        return '\n'.join(result)
    
    def save_analysis(self, analysis: Dict[str, Any], output_dir: str) -> Dict[str, str]:
        """Save analysis to both markdown and JSON formats"""
        
        # Create output directory
        project_dir = Path(output_dir) / "projects" / analysis["project_id"] / "Analysis"
        project_dir.mkdir(parents=True, exist_ok=True)
        
        # Save JSON format
        json_file = project_dir / "w5h-analysis.json"
        with open(json_file, 'w', encoding='utf-8') as f:
            json.dump(analysis, f, indent=2, ensure_ascii=False)
        
        # Save Markdown format  
        markdown_file = project_dir / "w5h-analysis.md"
        markdown_content = self.generate_markdown_report(analysis)
        with open(markdown_file, 'w', encoding='utf-8') as f:
            f.write(markdown_content)
        
        return {
            "markdown_file": str(markdown_file),
            "json_file": str(json_file)
        }


def main():
    """Command line interface for W5H analysis"""
    import sys
    
    if len(sys.argv) < 3:
        print("Usage: python process_w5h.py <project_id> <requirements_file>")
        sys.exit(1)
    
    project_id = sys.argv[1]
    requirements_file = sys.argv[2]
    
    if not os.path.exists(requirements_file):
        print(f"Requirements file not found: {requirements_file}")
        sys.exit(1)
    
    # Read requirements file
    with open(requirements_file, 'r', encoding='utf-8') as f:
        requirements_text = f.read()
    
    # Analyze requirements
    analyzer = W5HAnalyzer()
    analysis = analyzer.analyze_requirements(requirements_text, project_id)
    
    # Save results
    output_dir = "outputs"
    files = analyzer.save_analysis(analysis, output_dir)
    
    print(f"W5H analysis completed for project: {project_id}")
    print(f"Markdown report: {files['markdown_file']}")
    print(f"JSON data: {files['json_file']}")


if __name__ == "__main__":
    main()