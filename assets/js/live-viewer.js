/**
 * Viewer side of the chunked-upload live approach: the broadcast file at
 * uploads/live/{key}.webm keeps growing as the broadcaster's chunks land.
 * We poll live_status.php for its size; whenever it grows we reload the
 * <video> against the latest bytes and jump to the live edge. This trades a
 * few seconds of latency for not needing any RTMP/HLS media server.
 */
function initViewer(streamKey, videoEl) {
  let lastSize = -1;

  function reloadToLiveEdge() {
    const src = 'uploads/live/' + encodeURIComponent(streamKey) + '.webm?t=' + Date.now();
    const wasPlaying = !videoEl.paused;
    videoEl.src = src;
    videoEl.addEventListener(
      'loadedmetadata',
      () => {
        if (isFinite(videoEl.duration) && videoEl.duration > 2) {
          videoEl.currentTime = Math.max(0, videoEl.duration - 1.5);
        }
        if (wasPlaying || videoEl.autoplay) videoEl.play().catch(() => {});
      },
      { once: true }
    );
  }

  function poll() {
    fetch('live_status.php?key=' + encodeURIComponent(streamKey))
      .then((r) => r.json())
      .then((data) => {
        const viewerCountEl = document.getElementById('viewerCount');
        if (viewerCountEl) viewerCountEl.textContent = data.viewer_count;

        if (!data.is_live) {
          clearInterval(intervalId);
          toast('This stream has ended.');
          return;
        }
        if (data.size > lastSize) {
          lastSize = data.size;
          reloadToLiveEdge();
        }
      })
      .catch(() => {});
  }

  poll();
  const intervalId = setInterval(poll, 4000);
}
