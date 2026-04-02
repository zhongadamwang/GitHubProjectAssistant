#!/usr/bin/env python3
"""
Test script for Process W5H skill
"""

import os
import tempfile
import json
from pathlib import Path
from process_w5h import W5HAnalyzer


def test_w5h_analysis():
    """Test basic W5H analysis functionality"""
    
    # Sample requirements text
    requirements = """
    # E-commerce Platform Requirements
    
    ## Stakeholders
    - Small business owners (primary users)
    - Customers
    - Development team
    
    ## Requirements
    - User registration and authentication
    - Product catalog management
    - Shopping cart functionality
    - Order processing
    
    ## Timeline
    - MVP in 3 months
    - Full launch in 6 months
    
    ## Success Metrics
    - 50 orders per day within first month
    - 99.5% uptime
    """
    
    # Initialize analyzer
    analyzer = W5HAnalyzer()
    
    # Perform analysis
    project_id = "TEST-001"
    analysis = analyzer.analyze_requirements(requirements, project_id)
    
    # Verify analysis structure
    assert analysis["project_id"] == project_id
    assert analysis["analysis_framework"] == "W5H"
    assert "who" in analysis
    assert "what" in analysis 
    assert "when" in analysis
    assert "where" in analysis
    assert "why" in analysis
    assert "how" in analysis
    
    print("✓ W5H analysis structure validation passed")
    
    # Test markdown generation
    markdown_report = analyzer.generate_markdown_report(analysis)
    assert "# W5H Requirements Analysis" in markdown_report
    assert "WHO - Stakeholders and Roles" in markdown_report
    assert "WHAT - Functional and Non-Functional Requirements" in markdown_report
    assert "WHEN - Timeline and Milestones" in markdown_report
    assert "WHERE - Context and Environment" in markdown_report
    assert "WHY - Purpose and Business Rationale" in markdown_report
    assert "HOW - Implementation Approach and Methods" in markdown_report
    
    print("✓ Markdown report generation passed")
    
    # Test file saving
    with tempfile.TemporaryDirectory() as temp_dir:
        files = analyzer.save_analysis(analysis, temp_dir)
        
        # Verify files were created
        assert os.path.exists(files["markdown_file"])
        assert os.path.exists(files["json_file"])
        
        # Verify JSON content
        with open(files["json_file"], 'r') as f:
            saved_analysis = json.load(f)
            assert saved_analysis["project_id"] == project_id
        
        # Verify markdown content
        with open(files["markdown_file"], 'r') as f:
            saved_markdown = f.read()
            assert "W5H Requirements Analysis" in saved_markdown
    
    print("✓ File saving and loading passed")
    
    print("\n🎉 All tests passed! W5H skill is working correctly.")


def test_with_sample_data():
    """Test with sample data files"""
    
    skill_dir = Path(__file__).parent
    test_data_dir = skill_dir / "test_data"
    
    if not test_data_dir.exists():
        print("⚠️  Test data directory not found, skipping sample data test")
        return
    
    analyzer = W5HAnalyzer()
    
    # Test with each sample file
    for sample_file in test_data_dir.glob("*.md"):
        print(f"Testing with sample file: {sample_file.name}")
        
        with open(sample_file, 'r', encoding='utf-8') as f:
            requirements_text = f.read()
        
        project_id = f"SAMPLE-{sample_file.stem.upper()}"
        analysis = analyzer.analyze_requirements(requirements_text, project_id)
        
        # Basic validation
        assert analysis["project_id"] == project_id
        assert len(requirements_text) > 0
        
        print(f"✓ Analysis completed for {sample_file.name}")
    
    print("✓ Sample data testing completed")


if __name__ == "__main__":
    print("Testing Process W5H Analysis Skill")
    print("=" * 40)
    
    test_w5h_analysis()
    test_with_sample_data()
    
    print("\n🎯 Process W5H skill is ready for use!")