from flask import Flask, request, jsonify
import face_recognition
import json
import os
import tempfile

app = Flask(__name__)
python_face_api = app

THRESHOLD = float(os.getenv('FACE_THRESHOLD', '0.50'))


@app.get('/')
def index():
    return jsonify({
        'success': True,
        'service': 'python-face-vector',
        'endpoints': {
            'health': 'GET /health',
            'encode': 'POST /encode',
            'verify': 'POST /verify',
        },
    })


def read_image():
    if 'image' not in request.files:
        raise ValueError('File image wajib dikirim.')
    f = request.files['image']
    if not f.filename:
        raise ValueError('Nama file gambar kosong.')
    data = f.read()
    if not data or len(data) > 5 * 1024 * 1024:
        raise ValueError('Ukuran gambar tidak valid atau lebih dari 5 MB.')
    with tempfile.NamedTemporaryFile(suffix='.jpg', delete=False) as tmp:
        tmp.write(data)
        path = tmp.name
    try:
        image = face_recognition.load_image_file(path)
    finally:
        try:
            os.remove(path)
        except OSError:
            pass
    return image


@app.get('/health')
def health():
    return jsonify({'success': True, 'service': 'python-face-vector', 'threshold': THRESHOLD})


@app.post('/encode')
def encode():
    try:
        image = read_image()
        locations = face_recognition.face_locations(image, model='hog')
        if len(locations) == 0:
            return jsonify({'success': False, 'message': 'Wajah tidak terdeteksi pada foto.'}), 422
        if len(locations) > 1:
            return jsonify({'success': False, 'message': 'Terdeteksi lebih dari satu wajah. Pastikan hanya satu orang di kamera.'}), 422
        encodings = face_recognition.face_encodings(image, known_face_locations=locations, num_jitters=1)
        if not encodings:
            return jsonify({'success': False, 'message': 'Face encoding gagal dibuat.'}), 422
        vector = [float(x) for x in encodings[0]]
        return jsonify({'success': True, 'dimension': len(vector), 'vector': vector})
    except Exception as exc:
        return jsonify({'success': False, 'message': f'Python face API error: {exc}'}), 500


@app.post('/verify')
def verify():
    try:
        stored_raw = request.form.get('stored_vector', '')
        stored = json.loads(stored_raw)
        if not isinstance(stored, list) or len(stored) != 128:
            return jsonify({'success': False, 'message': 'Face vector tersimpan harus berisi 128 angka.'}), 400
        image = read_image()
        locations = face_recognition.face_locations(image, model='hog')
        if len(locations) == 0:
            return jsonify({'success': False, 'message': 'Wajah tidak terdeteksi pada foto.'}), 422
        if len(locations) > 1:
            return jsonify({'success': False, 'message': 'Terdeteksi lebih dari satu wajah.'}), 422
        encodings = face_recognition.face_encodings(image, known_face_locations=locations, num_jitters=1)
        if not encodings:
            return jsonify({'success': False, 'message': 'Face encoding gagal dibuat.'}), 422
        distance = float(face_recognition.face_distance([stored], encodings[0])[0])
        return jsonify({'success': True, 'match': distance <= THRESHOLD, 'distance': distance, 'threshold': THRESHOLD})
    except (json.JSONDecodeError, TypeError, ValueError) as exc:
        return jsonify({'success': False, 'message': f'Data vector tidak valid: {exc}'}), 400
    except Exception as exc:
        return jsonify({'success': False, 'message': f'Python face API error: {exc}'}), 500


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5002, debug=True)