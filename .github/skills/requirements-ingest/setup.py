#!/usr/bin/env python3
"""
Setup script for Requirements Ingest skill
"""

import subprocess
import sys
import os
from pathlib import Path

def check_python_version():
    """Ensure Python version is compatible"""
    if sys.version_info < (3, 7):
        print("❌ Python 3.7 or higher is required")
        sys.exit(1)
    print(f"✅ Python {sys.version.split()[0]} detected")

def install_dependencies():
    """Install required packages"""
    print("📦 Installing dependencies...")
    
    requirements_file = Path(__file__).parent / "requirements.txt"
    
    try:
        subprocess.run([
            sys.executable, "-m", "pip", "install", "-r", str(requirements_file)
        ], check=True, capture_output=True)
        print("✅ Dependencies installed successfully")
    except subprocess.CalledProcessError as e:
        print(f"❌ Failed to install dependencies: {e}")
        print("Try installing manually:")
        print(f"pip install -r {requirements_file}")
        return False
    return True

def run_tests():
    """Run basic tests"""
    print("🧪 Running tests...")
    
    test_script = Path(__file__).parent / "src" / "test_requirements_ingest.py"
    
    try:
        # Add src to Python path
        src_path = str(Path(__file__).parent / "src")
        env = os.environ.copy()
        env["PYTHONPATH"] = src_path + ":" + env.get("PYTHONPATH", "")
        
        subprocess.run([
            sys.executable, str(test_script)
        ], check=True, env=env)
        print("✅ All tests passed")
        return True
    except subprocess.CalledProcessError:
        print("❌ Tests failed")
        return False

def main():
    """Setup the Requirements Ingest skill"""
    print("🚀 Setting up Requirements Ingest Skill")
    print("=" * 40)
    
    # Check Python version
    check_python_version()
    
    # Install dependencies (optional, may fail in some environments)
    deps_ok = install_dependencies()
    
    if not deps_ok:
        print("\n⚠️  Dependencies not installed automatically")
        print("You may need to install them manually:")
        print("pip install pdfplumber python-docx PyPDF2")
    
    # Run tests
    if deps_ok:
        tests_ok = run_tests()
        if tests_ok:
            print("\n🎉 Setup completed successfully!")
            print("\nUsage:")
            print("python src/requirements_ingest.py PROJECT_ID file1.pdf file2.docx")
        else:
            print("\n⚠️  Setup completed with test failures")
    else:
        print("\n⚠️  Setup completed but dependencies need manual installation")

if __name__ == "__main__":
    main()