/**
 * Broadcaster side of the chunked-upload live streaming approach: capture the
 * camera/mic locally, record continuously with a short timeslice, and POST
 * each chunk to live_chunk.php as it becomes available. Because all chunks
 * come from ONE continuous MediaRecorder session, appending them in order on
 * the server reconstructs a single valid, playable file — no RTMP/HLS media
 * server required.
 */
async function initBroadcast(streamKey, previewEl) {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
    previewEl.srcObject = stream;
    previewEl.muted = true;

    const recorder = new MediaRecorder(stream, { mimeType: 'video/webm;codecs=vp8,opus' });
    recorder.ondataavailable = async (e) => {
      if (e.data.size === 0) return;
      try {
        await fetch('live_chunk.php?key=' + encodeURIComponent(streamKey), {
          method: 'POST',
          headers: { 'X-CSRF-Token': window.CSRF_TOKEN, 'Content-Type': 'application/octet-stream' },
          body: e.data,
        });
      } catch (err) {
        console.error('Chunk upload failed', err);
      }
    };
    recorder.start(3000); // emit a chunk every 3 seconds

    window.addEventListener('beforeunload', () => {
      if (recorder.state !== 'inactive') recorder.stop();
      stream.getTracks().forEach((t) => t.stop());
    });
  } catch (err) {
    toast('Could not access camera/microphone. Check permissions.', 'error');
  }
}
