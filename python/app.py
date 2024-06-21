from flask import Flask, request, jsonify
from tokens import num_tokens, encoded_from_string
from bs4 import BeautifulSoup as bs
from semantic_text_splitter import TextSplitter

app = Flask(__name__)

@app.route('/')
def home():
    html_content = """
      <h1>Current endpoints</h1>
      <ul>
        <li>
          /tokens - POST JSON with:
          <ul>
            <li>string - A string to tokenize</li>
            <li>encoding - The encoding name (See <a href="https://github.com/openai/openai-cookbook/blob/main/examples/How_to_count_tokens_with_tiktoken.ipynb">Encodings</a>)</li>
            <li>model - The model name (See same link above)</li>
        </li>
        <li>
          /html-to-text - POST JSON with:
          <ul>
            <li>string - A string of HTML to convert to plain text</li>
          </ul>
        </li>
        <li>
          /splitter - POST JSON with:
          <ul>
            <li>string - A string of HTML to split</li>
            <li>max_characters - The maximum number of characters to split on</li>
          </ul>
        </li>
      </ul>
    """
    return html_content

@app.route('/tokens', methods=['POST'])
def tokens():
    data = request.get_json()

    # Process the data
    data['num_tokens'] = num_tokens(data['string'], data['encoding'])
    data = encoded_from_string(data['string'], data['encoding'], data['model'])
    response = {'status': 'success', 'data': data}
    return jsonify(response)

@app.route('/html-to-text', methods=['POST'])
def htmlToText():
    data = request.get_json()
    soup = bs(data['string'], 'html.parser')
    response = {'status': 'success', 'data': soup.get_text("\r\n", strip=True)}
    return jsonify(response)

@app.route('/splitter', methods=['POST'])
def splitter():
    data = request.get_json()
    splitter = TextSplitter(int(data['max_characters']))
    chunks = splitter.chunks(data['string'])
    response = {'status': 'success', 'data': chunks}
    return jsonify(response)

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5001)
