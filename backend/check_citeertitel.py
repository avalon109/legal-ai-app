import os
from pathlib import Path
import xml.etree.ElementTree as ET

def check_citeertitel():
    # Get the laws directory path
    laws_dir = Path(__file__).parent / 'laws'
    
    # Find all XML files
    xml_files = list(laws_dir.glob('*.xml'))
    
    print(f"Found {len(xml_files)} XML files to check")
    
    # Track results
    files_with_citeertitel = []
    files_without_citeertitel = []
    
    # Check each file
    for xml_file in xml_files:
        try:
            # Parse the XML file
            tree = ET.parse(xml_file)
            root = tree.getroot()
            
            # Look for citeertitel element
            citeertitel = root.find('.//citeertitel')
            
            if citeertitel is not None:
                files_with_citeertitel.append({
                    'file': xml_file.name,
                    'content': citeertitel.text
                })
            else:
                files_without_citeertitel.append(xml_file.name)
                
        except Exception as e:
            print(f"Error processing {xml_file.name}: {str(e)}")
    
    # Print results
    print("\nFiles WITH citeertitel element:")
    for file_info in files_with_citeertitel:
        print(f"- {file_info['file']}: {file_info['content']}")
    
    print("\nFiles WITHOUT citeertitel element:")
    for filename in files_without_citeertitel:
        print(f"- {filename}")
    
    print(f"\nSummary:")
    print(f"Total files checked: {len(xml_files)}")
    print(f"Files with citeertitel: {len(files_with_citeertitel)}")
    print(f"Files without citeertitel: {len(files_without_citeertitel)}")

if __name__ == "__main__":
    check_citeertitel() 