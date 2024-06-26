import magic
from langchain_community.document_loaders import PyPDFLoader
import markdown
from unstructured.partition.doc import partition_doc
from unstructured.partition.ppt import partition_ppt
import json

def getDocContent(path: str) -> str:
      """Returns the document content."""
      mimeType = getMimeType(path)

      if "pdf" in mimeType:
            docContent = getPDFContent(path)
      elif "officedocument.wordprocessingml" in mimeType:
            docContent = getWordContent(path)
      elif "officedocument.presentationml" in mimeType:
            docContent = getPowerpointContent(path)
      else:
            docContent = "Unsupported document type"

      return docContent

def getMimeType(path: str) -> str:
      mime = magic.Magic(mime=True)
      mimeType = mime.from_file(path)
      return mimeType

def getPDFContent(path: str) -> str:
      loader = PyPDFLoader(path, extract_images=True)
      # pages = loader.load_and_split()
      pages = loader.load()
      pdfContent = ""
      for page in pages:
        pdfContent = pdfContent+page.page_content
      return markdown.markdown(pdfContent, extensions=['nl2br'])

def getWordContent(path: str) -> str:
     elements = partition_doc(filename=path)
     wordContent = ""
     for element in elements:
       wordContent = wordContent+"\n\r"+element.text
     return markdown.markdown(wordContent, extensions=['nl2br'])

def getPowerpointContent(path: str) -> str:
     elements = partition_ppt(filename=path)
     ppContent = ""
     for element in elements:
       ppContent = ppContent+"\n\r"+element.text
     return markdown.markdown(ppContent, extensions=['nl2br'])
