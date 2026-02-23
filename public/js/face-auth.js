// face-auth.js - reusable face api logic for register & login modal
(function(){
  const MODELS_PATH = '/models';
  let videoEl, overlay, overlayCtx, spinnerEl, statusEl, successEl, errorEl, captureBtn, cancelBtn;
  let currentAction = null; // 'register' or 'login'

  async function loadModels(){
    if (window._faceModelsLoaded) return;
    await faceapi.nets.tinyFaceDetector.loadFromUri(MODELS_PATH + '/tiny_face_detector');
    await faceapi.nets.faceLandmark68Net.loadFromUri(MODELS_PATH + '/face_landmark_68');
    await faceapi.nets.faceRecognitionNet.loadFromUri(MODELS_PATH + '/face_recognition');
    window._faceModelsLoaded = true;
  }

  async function startCamera(){
    try{
      const stream = await navigator.mediaDevices.getUserMedia({video:{ facingMode: 'user' }});
      videoEl.srcObject = stream;
      await videoEl.play();
      statusEl.textContent = 'Camera ready';
      return true;
    } catch(e){
      statusEl.textContent = 'Camera access denied: ' + e.message;
      showError('Camera permission denied');
      return false;
    }
  }

  function stopCamera(){
    const stream = videoEl.srcObject;
    if (stream){
      stream.getTracks().forEach(t=>t.stop());
      videoEl.srcObject = null;
    }
  }

  function drawBox(box){
    overlayCtx.clearRect(0,0,overlay.width, overlay.height);
    overlayCtx.strokeStyle = '#0d6efd';
    overlayCtx.lineWidth = 2;
    overlayCtx.strokeRect(box.x, box.y, box.width, box.height);
  }

  async function detectFaceLoop(){
    captureBtn.disabled = true;
    const options = new faceapi.TinyFaceDetectorOptions();
    try{
      const res = await faceapi.detectSingleFace(videoEl, options).withFaceLandmarks();
      overlayCtx.clearRect(0,0,overlay.width, overlay.height);
      if (res){
        const box = res.detection.box;
        drawBox(box);
        statusEl.textContent = 'Face detected';
        captureBtn.disabled = false;
        return true;
      } else {
        statusEl.textContent = 'No face detected';
        captureBtn.disabled = true;
        return false;
      }
    } catch(e){
      console.error(e);
      statusEl.textContent = 'Detection error';
      return false;
    }
  }

  async function captureDescriptor(){
    statusEl.textContent = 'Capturing...';
    spinnerEl.style.display = 'inline-block';
    try{
      const res = await faceapi.detectSingleFace(videoEl, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceDescriptor();
      if (!res){
        showError('No face detected');
        spinnerEl.style.display = 'none';
        return null;
      }
      const descriptor = Array.from(res.descriptor);
      spinnerEl.style.display = 'none';
      return descriptor;
    } catch(e){
      spinnerEl.style.display = 'none';
      showError('Capture failed');
      return null;
    }
  }

  function showSuccess(msg){
    successEl.textContent = msg;
    successEl.style.display = 'block';
    errorEl.style.display = 'none';
  }

  function showError(msg){
    errorEl.textContent = msg;
    errorEl.style.display = 'block';
    successEl.style.display = 'none';
  }

  async function sendToBackend(action, descriptor, csrfToken){
    const url = action === 'register' ? '/face/register' : '/face/login';
    try{
      const resp = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ descriptor: descriptor, _csrf_token: csrfToken })
      });
      const data = await resp.json();
      return { ok: resp.ok, data };
    } catch(e){
      return { ok: false, data: { success: false, message: e.message } };
    }
  }

  async function openModal(action, csrfToken){
    currentAction = action;
    // show/hide elements
    successEl.style.display = 'none'; errorEl.style.display='none'; spinnerEl.style.display='none';
    statusEl.textContent = 'Loading models...';
    await loadModels();
    statusEl.textContent = 'Models loaded';
    const started = await startCamera();
    if (!started) return;

    // run one detection to enable capture
    await detectFaceLoop();

    // attach continuous detection while modal open
    videoEl._faceInterval = setInterval(detectFaceLoop, 700);

    // show bootstrap modal
    const bsModal = new bootstrap.Modal(document.getElementById('faceCameraModal'));
    bsModal.show();

    // wire capture
    captureBtn.onclick = async ()=>{
      captureBtn.disabled = true;
      const descriptor = await captureDescriptor();
      if (!descriptor) { captureBtn.disabled = false; return; }
      statusEl.textContent = 'Sending...';
      const result = await sendToBackend(action, descriptor, csrfToken);
      if (result.ok && result.data.success){
        if (action === 'register'){
          showSuccess('Face Registered Successfully');
        } else {
          statusEl.textContent = 'Login successful, redirecting...';
          // redirect to server-provided url or root
          window.location.href = result.data.redirect || '/';
        }
      } else {
        showError(result.data.message || 'Operation failed');
        captureBtn.disabled = false;
      }
    };

    cancelBtn.onclick = ()=>{
      bsModal.hide();
    };

    // cleanup on modal hide
    document.getElementById('faceCameraModal').addEventListener('hidden.bs.modal', ()=>{
      clearInterval(videoEl._faceInterval);
      stopCamera();
      overlayCtx.clearRect(0,0,overlay.width, overlay.height);
      captureBtn.disabled = true;
    }, { once: true });
  }

  function init(){
    // DOM elements
    videoEl = document.getElementById('face-video');
    overlay = document.getElementById('face-overlay');
    overlayCtx = overlay.getContext('2d');
    spinnerEl = document.getElementById('face-spinner');
    statusEl = document.getElementById('face-status');
    successEl = document.getElementById('face-success');
    errorEl = document.getElementById('face-error');
    captureBtn = document.getElementById('face-capture');
    cancelBtn = document.getElementById('face-cancel');

    // Expose open function
    window.FaceAuth = { openModal };
  }

  // initialize when DOM ready
  document.addEventListener('DOMContentLoaded', ()=>{
    if (document.getElementById('faceCameraModal')) init();

    // Attach click handlers to trigger buttons (use data-csrf to avoid quoting issues)
    const regBtn = document.getElementById('open-face-register');
    if (regBtn){
      regBtn.addEventListener('click', ()=>{
        const token = regBtn.getAttribute('data-csrf');
        if (window.FaceAuth) window.FaceAuth.openModal('register', token);
      });
    }
    const loginBtn = document.getElementById('open-face-login');
    if (loginBtn){
      loginBtn.addEventListener('click', ()=>{
        const token = loginBtn.getAttribute('data-csrf');
        if (window.FaceAuth) window.FaceAuth.openModal('login', token);
      });
    }
  });

})();
