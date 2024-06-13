from flask import Flask, request, jsonify
from tokens import num_tokens, encoded_from_string

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
            <li>encoding - The endcoding name (See <a href="https://github.com/openai/openai-cookbook/blob/main/examples/How_to_count_tokens_with_tiktoken.ipynb">Encodings</a>)</li>
            <li>model - The model name (See same link above)</li>
        </li>
      </ul>"
    """
    return html_content

@app.route('/tokens', methods=['POST'])
def process():
    data = request.get_json()

    # Process the data
    data['num_tokens'] = num_tokens(data['string'], data['encoding'])
    data['encoded'] = encoded_from_string(data['string'], data['encoding'], data['model'])
    response = {'status': 'success', 'data': data}
    return jsonify(response)

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5001)
