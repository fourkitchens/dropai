import tiktoken
from typing import List

def num_tokens(string: str, encoding: str) -> int:
      """Returns the number of tokens in a text string."""
      encoding = tiktoken.get_encoding(encoding)
      num_tokens = len(encoding.encode(string))
      return num_tokens

def encoded_from_string(string: str, encoding: str, model: str) -> List[int]:
      """Returns an encoded string."""
      encoding = tiktoken.get_encoding(encoding)
      encoding = tiktoken.encoding_for_model(model)
      encoded = encoding.encode(string)
      return encoded
