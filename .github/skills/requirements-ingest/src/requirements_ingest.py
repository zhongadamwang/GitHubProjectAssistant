"""
Requirements Ingest System
Normalizes requirements from multiple file formats into structured chunks.
Outputs to structured folder system for downstream processing.
"""

import json
import re
import os
import hashlib
from datetime import datetime
from typing import List, Dict, Any, Tuple, Optional
from pathlib import Path
from dataclasses import dataclass
import uuid

# File format handlers
try:
    import PyPDF2
    import pdfplumber
except ImportError:
    PyPDF2 = None
    pdfplumber = None

try:
    from docx import Document
except ImportError:
    Document = None

try:
    from email import message_from_string
    from email.policy import default
except ImportError:
    message_from_string = None

@dataclass
class RequirementChunk:
    """Single atomic requirement with metadata"""
    id: str
    source_file: str
    location_hint: str
    text: str
    tags: List[str]
    confidence: float

class RequirementsIngestor:
    """Main class for requirements ingestion and processing"""
    
    def __init__(self, output_base_dir: str = "./outputs"):
        """Initialize with configurable output directory"""
        self.output_base_dir = Path(output_base_dir)
        self.classification_keywords = {
            'functional': [
                'shall', 'must', 'will', 'should', 'function', 'feature', 
                'user', 'system', 'process', 'calculate', 'display', 'store'
            ],
            'nonfunctional': [
                'performance', 'speed', 'response time', 'security', 'usability',
                'reliability', 'availability', 'scalability', 'throughput'
            ],
            'constraint': [
                'budget', 'timeline', 'technology', 'regulation', 'compliance',
                'limitation', 'restriction', 'cannot', 'prohibited'
            ],
            'assumption': [
                'assume', 'given', 'provided', 'available', 'expected',
                'prerequisite', 'dependency', 'relies on'
            ],
            'out-of-scope': [
                'out of scope', 'excluded', 'not included', 'future release',
                'phase 2', 'beyond', 'outside'
            ]
        }
    
    def process_files(self, files: List[str], project_id: str, save_to_file: bool = True) -> Dict[str, Any]:
        """Main entry point for processing multiple files"""
        start_time = datetime.now()
        all_requirements = []
        all_glossary_terms = []
        processing_log = {
            "project_id": project_id,
            "processing_session": {
                "timestamp": start_time.isoformat(),
                "version": "1.0",
                "tool_version": "requirements-ingest-v2.1",
                "user": os.environ.get("USERNAME", "unknown"),
                "method": "traditional_script"
            },
            "input_files": [],
            "processing_stats": {},
            "errors": [],
            "warnings": []
        }
        
        for file_path in files:
            file_info = {
                "file_path": file_path,
                "file_size": 0,
                "file_hash": "",
                "processed_successfully": False,
                "requirements_extracted": 0
            }
            
            try:
                # Get file info
                if os.path.exists(file_path):
                    file_info["file_size"] = os.path.getsize(file_path)
                    file_info["file_hash"] = self._calculate_file_hash(file_path)
                
                chunks = self._process_single_file(file_path)
                all_requirements.extend(chunks)
                file_info["processed_successfully"] = True
                file_info["requirements_extracted"] = len(chunks)
                
                # Extract glossary candidates
                text_content = ' '.join([chunk.text for chunk in chunks])
                glossary_terms = self._extract_glossary_suspects(text_content)
                all_glossary_terms.extend(glossary_terms)
                
            except Exception as e:
                # Add error chunk for problematic files
                error_chunk = RequirementChunk(
                    id=f"R-ERROR-{len(all_requirements) + 1:03d}",
                    source_file=file_path,
                    location_hint="file processing error",
                    text=f"Error processing file: {str(e)}",
                    tags=["assumption"],
                    confidence=0.1
                )
                all_requirements.append(error_chunk)
                processing_log["errors"].append(f"Failed to process {file_path}: {str(e)}")
                file_info["requirements_extracted"] = 1  # Error chunk
            
            processing_log["input_files"].append(file_info)
        
        # Remove duplicate glossary terms and filter
        unique_glossary = list(set(all_glossary_terms))
        
        # Calculate processing stats
        end_time = datetime.now()
        processing_time = (end_time - start_time).total_seconds()
        total_confidence = sum(chunk.confidence for chunk in all_requirements)
        avg_confidence = total_confidence / len(all_requirements) if all_requirements else 0
        
        # Add warnings for low confidence requirements
        for chunk in all_requirements:
            if chunk.confidence < 0.5:
                processing_log["warnings"].append(f"Low confidence requirement {chunk.id} ({chunk.confidence:.2f})")
        
        processing_log["processing_stats"] = {
            "total_files": len(files),
            "successful_files": sum(1 for f in processing_log["input_files"] if f["processed_successfully"]),
            "failed_files": len(processing_log["errors"]),
            "total_requirements": len(all_requirements),
            "avg_confidence": round(avg_confidence, 2),
            "processing_time_seconds": round(processing_time, 2)
        }
        
        # Prepare main output
        requirements_output = {
            "project_id": project_id,
            "generated_at": end_time.isoformat(),
            "version": "1.0",
            "total_requirements": len(all_requirements),
            "requirements": [self._chunk_to_dict(chunk) for chunk in all_requirements],
            "glossary_suspects": unique_glossary,
            "processing_summary": processing_log["processing_stats"]
        }
        
        # Prepare glossary output
        glossary_output = self._create_glossary_output(project_id, unique_glossary, all_requirements)
        
        # Save to files if requested
        if save_to_file:
            output_paths = self._save_outputs(project_id, requirements_output, processing_log, glossary_output, files)
            print(f"✅ Requirements processed and saved to: {output_paths['base_dir']}")
            print(f"   📋 Requirements (JSON): {output_paths['requirements_json']}")
            print(f"   📄 Requirements (MD): {output_paths['requirements_md']}")
            print(f"   📊 Processing Log: {output_paths['log']}")
            print(f"   📚 Glossary: {output_paths['glossary']}")
            
        return requirements_output
    
    def _process_single_file(self, file_path: str) -> List[RequirementChunk]:
        """Process a single file and extract requirements"""
        file_ext = Path(file_path).suffix.lower()
        
        if file_ext == '.pdf':
            return self._process_pdf(file_path)
        elif file_ext in ['.docx', '.doc']:
            return self._process_docx(file_path)
        elif file_ext in ['.md', '.markdown']:
            return self._process_markdown(file_path)
        elif file_ext in ['.eml', '.email', '.txt']:
            return self._process_email(file_path)
        else:
            # Fallback: treat as plain text
            return self._process_text(file_path)
    
    def _process_pdf(self, file_path: str) -> List[RequirementChunk]:
        """Extract requirements from PDF files"""
        chunks = []
        
        if not pdfplumber:
            raise ImportError("PDF processing requires pdfplumber: pip install pdfplumber")
        
        with pdfplumber.open(file_path) as pdf:
            for page_num, page in enumerate(pdf.pages, 1):
                text = page.extract_text()
                if text:
                    page_chunks = self._split_into_chunks(
                        text, 
                        file_path, 
                        f"page {page_num}"
                    )
                    chunks.extend(page_chunks)
        
        return chunks
    
    def _process_docx(self, file_path: str) -> List[RequirementChunk]:
        """Extract requirements from DOCX files"""
        if not Document:
            raise ImportError("DOCX processing requires python-docx: pip install python-docx")
        
        doc = Document(file_path)
        chunks = []
        
        for para_num, paragraph in enumerate(doc.paragraphs, 1):
            if paragraph.text.strip():
                chunk_text = paragraph.text.strip()
                if self._is_requirement_candidate(chunk_text):
                    chunk = self._create_chunk(
                        chunk_text, 
                        file_path, 
                        f"paragraph {para_num}"
                    )
                    chunks.append(chunk)
        
        return chunks
    
    def _process_markdown(self, file_path: str) -> List[RequirementChunk]:
        """Extract requirements from Markdown files"""
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Split by headers to get sections
        sections = re.split(r'^#{1,6}\s+', content, flags=re.MULTILINE)
        chunks = []
        
        for i, section in enumerate(sections):
            if section.strip():
                section_chunks = self._split_into_chunks(
                    section, 
                    file_path, 
                    f"section {i+1}"
                )
                chunks.extend(section_chunks)
        
        return chunks
    
    def _process_email(self, file_path: str) -> List[RequirementChunk]:
        """Extract requirements from email files"""
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        if message_from_string:
            try:
                email_msg = message_from_string(content, policy=default)
                body = email_msg.get_body(preferencelist=('plain', 'html'))
                if body:
                    content = str(body)
            except:
                pass  # Fallback to treating as plain text
        
        return self._split_into_chunks(content, file_path, "email body")
    
    def _process_text(self, file_path: str) -> List[RequirementChunk]:
        """Fallback processor for plain text files"""
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        return self._split_into_chunks(content, file_path, "text file")
    
    def _split_into_chunks(self, text: str, source_file: str, location_hint: str) -> List[RequirementChunk]:
        """Split text into atomic requirement chunks"""
        chunks = []
        
        # Split by sentences first
        sentences = re.split(r'[.!?]+', text)
        
        current_chunk = ""
        sentence_count = 0
        
        for sentence in sentences:
            sentence = sentence.strip()
            if not sentence:
                continue
            
            sentence_count += 1
            test_chunk = current_chunk + " " + sentence if current_chunk else sentence
            
            # Check if chunk is getting too long (≈400-600 tokens ≈ 300-450 words, optimized for modern LLMs)
            word_count = len(test_chunk.split())
            
            if word_count > 300 and current_chunk:
                # Save current chunk and start new one
                if self._is_requirement_candidate(current_chunk):
                    chunk = self._create_chunk(
                        current_chunk,
                        source_file,
                        f"{location_hint}, sent {sentence_count - 1}"
                    )
                    chunks.append(chunk)
                current_chunk = sentence
            else:
                current_chunk = test_chunk
        
        # Handle the last chunk
        if current_chunk and self._is_requirement_candidate(current_chunk):
            chunk = self._create_chunk(
                current_chunk,
                source_file,
                f"{location_hint}, sent {sentence_count}"
            )
            chunks.append(chunk)
        
        return chunks
    
    def _is_requirement_candidate(self, text: str) -> bool:
        """Determine if text contains requirements"""
        text_lower = text.lower()
        
        # Skip very short texts
        if len(text.split()) < 3:
            return False
        
        # Look for requirement indicators
        requirement_indicators = [
            'shall', 'must', 'will', 'should', 'requires', 'needs',
            'system', 'user', 'application', 'feature', 'function'
        ]
        
        return any(indicator in text_lower for indicator in requirement_indicators)
    
    def _create_chunk(self, text: str, source_file: str, location_hint: str) -> RequirementChunk:
        """Create a RequirementChunk with classification"""
        # Generate unique ID
        chunk_id = f"R-{hash(text + source_file + location_hint) % 10000:04d}"
        
        # Classify the requirement
        tags = self._classify_requirement(text)
        
        # Calculate confidence based on text clarity
        confidence = self._calculate_confidence(text)
        
        return RequirementChunk(
            id=chunk_id,
            source_file=Path(source_file).name,
            location_hint=location_hint,
            text=text.strip(),
            tags=tags,
            confidence=confidence
        )
    
    def _classify_requirement(self, text: str) -> List[str]:
        """Classify requirement into tags"""
        text_lower = text.lower()
        tags = []
        
        for category, keywords in self.classification_keywords.items():
            if any(keyword in text_lower for keyword in keywords):
                tags.append(category)
        
        # Default to functional if no other tags
        if not tags:
            tags = ['functional']
        
        return tags
    
    def _calculate_confidence(self, text: str) -> float:
        """Calculate confidence score based on text clarity"""
        score = 0.5  # Base score
        
        # Higher confidence for clear requirement language
        if any(word in text.lower() for word in ['shall', 'must', 'will']):
            score += 0.3
        
        # Higher confidence for specific measurements
        if re.search(r'\d+', text):
            score += 0.2
        
        # Lower confidence for vague language
        if any(word in text.lower() for word in ['maybe', 'probably', 'might', 'unclear']):
            score -= 0.3
        
        # Lower confidence for very long sentences
        if len(text.split()) > 50:
            score -= 0.2
        
        return max(0.0, min(1.0, score))
    
    def _extract_glossary_suspects(self, text: str) -> List[str]:
        """Extract potential glossary terms from text"""
        # Find technical terms (capitalized words, acronyms)
        words = re.findall(r'\b[A-Z][A-Za-z]*\b|\b[A-Z]{2,}\b', text)
        
        # Count occurrences
        word_counts = {}
        for word in words:
            if len(word) > 2:  # Skip short words
                word_counts[word] = word_counts.get(word, 0) + 1
        
        # Return words that appear 2+ times
        return [word for word, count in word_counts.items() if count >= 2]
    
    def _chunk_to_dict(self, chunk: RequirementChunk) -> Dict[str, Any]:
        """Convert RequirementChunk to dictionary"""
        return {
            "id": chunk.id,
            "source_file": chunk.source_file,
            "location_hint": chunk.location_hint,
            "text": chunk.text,
            "tags": chunk.tags,
            "confidence": round(chunk.confidence, 2)
        }
    
    def _calculate_file_hash(self, file_path: str) -> str:
        """Calculate SHA256 hash of file"""
        try:
            with open(file_path, 'rb') as f:
                return hashlib.sha256(f.read()).hexdigest()[:16]  # First 16 chars
        except:
            return "unknown"
    
    def _create_project_directory(self, project_id: str) -> Path:
        """Create and return project directory path with Analysis subfolder"""
        # Create Analysis subfolder to align with organizational structure
        analysis_dir = self.output_base_dir / "projects" / project_id / "Analysis"
        analysis_dir.mkdir(parents=True, exist_ok=True)
        
        # Create subdirectories within Analysis
        (analysis_dir / "source_files").mkdir(exist_ok=True)
        (analysis_dir / "versions").mkdir(exist_ok=True)
        
        return analysis_dir
    
    def _save_outputs(self, project_id: str, requirements: Dict, log: Dict, glossary: Dict, source_files: List[str]) -> Dict[str, str]:
        """Save all outputs to structured folders with dual format (JSON + Markdown)"""
        analysis_dir = self._create_project_directory(project_id)
        
        # Define file paths within Analysis folder
        requirements_json_file = analysis_dir / "requirements.json"
        requirements_md_file = analysis_dir / "requirements.md"  # NEW: Markdown output
        log_file = analysis_dir / "processing_log.json"
        glossary_file = analysis_dir / "glossary.json"
        
        # Handle versioning - backup existing files
        if requirements_json_file.exists():
            timestamp = datetime.now().strftime("%Y-%m-%d_%H-%M")
            version_json_file = analysis_dir / "versions" / f"v1_{timestamp}.json"
            requirements_json_file.rename(version_json_file)
        
        if requirements_md_file.exists():
            timestamp = datetime.now().strftime("%Y-%m-%d_%H-%M")
            version_md_file = analysis_dir / "versions" / f"v1_{timestamp}.md"
            requirements_md_file.rename(version_md_file)
        
        # Generate Markdown output matching original specification
        markdown_content = self._generate_markdown_output(requirements, source_files)
        
        # Save files (JSON + Markdown)
        with open(requirements_json_file, 'w', encoding='utf-8') as f:
            json.dump(requirements, f, indent=2, ensure_ascii=False)
        
        with open(requirements_md_file, 'w', encoding='utf-8') as f:  # NEW: Markdown file
            f.write(markdown_content)
        
        with open(log_file, 'w', encoding='utf-8') as f:
            json.dump(log, f, indent=2, ensure_ascii=False)
            
        with open(glossary_file, 'w', encoding='utf-8') as f:
            json.dump(glossary, f, indent=2, ensure_ascii=False)
        
        # Create source file mapping
        self._create_source_mapping(analysis_dir, log["input_files"])
        
        return {
            "base_dir": str(analysis_dir),
            "requirements_json": str(requirements_json_file),
            "requirements_md": str(requirements_md_file),  # NEW: Markdown output path
            "log": str(log_file),
            "glossary": str(glossary_file)
        }
    
    def _create_source_mapping(self, analysis_dir: Path, input_files: List[Dict]) -> None:
        """Create source file mapping and optionally copy files"""
        mapping = {
            "created_at": datetime.now().isoformat(),
            "source_files": []
        }
        
        for file_info in input_files:
            file_mapping = {
                "original_path": file_info["file_path"],
                "file_name": Path(file_info["file_path"]).name,
                "file_size": file_info["file_size"],
                "file_hash": file_info["file_hash"],
                "processed_successfully": file_info["processed_successfully"],
                "requirements_extracted": file_info["requirements_extracted"]
            }
            mapping["source_files"].append(file_mapping)
        
        mapping_file = analysis_dir / "source_files" / "file_mapping.json"
        with open(mapping_file, 'w', encoding='utf-8') as f:
            json.dump(mapping, f, indent=2, ensure_ascii=False)
    
    def _create_glossary_output(self, project_id: str, terms: List[str], requirements: List[RequirementChunk]) -> Dict:
        """Create enhanced glossary output"""
        glossary_data = {
            "project_id": project_id,
            "extracted_terms": [],
            "suggested_definitions": []
        }
        
        # Count term frequencies and find contexts
        term_info = {}
        for term in terms:
            contexts = []
            frequency = 0
            
            for chunk in requirements:
                if term.lower() in chunk.text.lower():
                    frequency += 1
                    # Extract sentence containing the term
                    sentences = chunk.text.split('.')
                    for sentence in sentences:
                        if term.lower() in sentence.lower():
                            contexts.append(sentence.strip())
                            break
            
            if frequency > 0:
                term_info[term] = {
                    "term": term,
                    "frequency": frequency,
                    "contexts": list(set(contexts))[:3],  # Max 3 unique contexts
                    "confidence": min(0.95, 0.5 + (frequency * 0.1))  # Higher freq = higher confidence
                }
        
        # Sort by frequency and add to output
        sorted_terms = sorted(term_info.values(), key=lambda x: x["frequency"], reverse=True)
        glossary_data["extracted_terms"] = sorted_terms[:20]  # Top 20 terms
        
        # Generate suggested definitions for high-frequency terms
        for term_data in sorted_terms[:10]:  # Top 10 get suggested definitions
            if term_data["frequency"] >= 3:
                # Find requirements that mention this term
                sources = []
                for chunk in requirements:
                    if term_data["term"].lower() in chunk.text.lower():
                        sources.append(chunk.id)
                
                suggestion = {
                    "term": term_data["term"],
                    "suggested_definition": f"Domain term appearing {term_data['frequency']} times across requirements",
                    "sources": sources[:5]  # Max 5 source references
                }
                glossary_data["suggested_definitions"].append(suggestion)
        
        return glossary_data
    
    def _generate_markdown_output(self, requirements_output: Dict, source_files: List[str]) -> str:
        """Generate Markdown output matching original specification"""
        project_id = requirements_output["project_id"]
        requirements = requirements_output["requirements"]
        glossary_suspects = requirements_output["glossary_suspects"]
        
        # Start with header
        markdown_lines = [
            "# Requirements Analysis Report",
            "",
            f"**Project**: {project_id}",
            f"**Source**: {', '.join([Path(f).name for f in source_files])}",
            f"**Generated**: {requirements_output.get('generated_at', 'Unknown')}",
            f"**Total Requirements**: {requirements_output.get('total_requirements', len(requirements))}",
            "",
            "## Requirements",
            "",
            "| ID | Section | Text | Tags | Confidence |",
            "|----|---------|------|------|-----------|"
        ]
        
        # Add each requirement as table row
        for req in requirements:
            # Clean text for table (remove newlines, limit length)
            clean_text = req["text"].replace("\n", " ").replace("|", "\\|")
            if len(clean_text) > 80:
                clean_text = clean_text[:77] + "..."
            
            # Map location_hint to section
            section = req.get("location_hint", "Unknown").replace("|", "\\|")
            
            # Format tags
            tags = ", ".join(req["tags"])
            
            # Map numeric confidence to text
            confidence = req["confidence"]
            if confidence >= 0.8:
                conf_text = "high"
            elif confidence >= 0.6:
                conf_text = "medium"
            else:
                conf_text = "low"
            
            markdown_lines.append(
                f"| {req['id']} | {section} | {clean_text} | {tags} | {conf_text} |"
            )
        
        # Add glossary section
        if glossary_suspects:
            markdown_lines.extend([
                "",
                "## Glossary Suspects",
                ""
            ])
            for term in sorted(glossary_suspects):
                markdown_lines.append(f"- {term}")
        
        # Add processing summary if available
        if "processing_summary" in requirements_output:
            summary = requirements_output["processing_summary"]
            markdown_lines.extend([
                "",
                "## Processing Summary",
                "",
                f"- **Files processed**: {summary.get('total_files', 'Unknown')}",
                f"- **Requirements extracted**: {summary.get('total_requirements', 'Unknown')}",
                f"- **Average confidence**: {summary.get('avg_confidence', 'Unknown')}",
                f"- **Processing time**: {summary.get('processing_time_seconds', 'Unknown')}s"
            ])
        
        return "\n".join(markdown_lines)


def main():
    """CLI interface for requirements ingestion with file output"""
    import sys
    import argparse
    
    parser = argparse.ArgumentParser(
        description="Requirements Ingest - Extract and normalize requirements",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  # Save to files (default)
  python requirements_ingest.py PROJECT-001 requirements.pdf specs.docx
  
  # Output to console only 
  python requirements_ingest.py PROJECT-001 requirements.pdf --no-save
  
  # Custom output directory
  python requirements_ingest.py PROJECT-001 requirements.pdf --output-dir /path/to/outputs
        """
    )
    
    parser.add_argument("project_id", help="Project identifier for organizing outputs")
    parser.add_argument("files", nargs="+", help="Input files to process (PDF, DOCX, MD, TXT, EML)")
    parser.add_argument("--no-save", action="store_true", help="Output to console only (don't save files)")
    parser.add_argument("--output-dir", default="./outputs", help="Base output directory (default: ./outputs)")
    parser.add_argument("--console-output", action="store_true", help="Also print JSON to console")
    
    args = parser.parse_args()
    
    # Create ingestor with custom output directory
    ingestor = RequirementsIngestor(output_base_dir=args.output_dir)
    
    # Process files
    save_to_file = not args.no_save
    result = ingestor.process_files(args.files, args.project_id, save_to_file=save_to_file)
    
    # Console output for backward compatibility or when explicitly requested
    if args.no_save or args.console_output:
        print(json.dumps(result, indent=2))


if __name__ == "__main__":
    main()