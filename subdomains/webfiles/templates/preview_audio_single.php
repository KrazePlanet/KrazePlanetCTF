<?php
/**
 * templates/preview_audio_single.php
 */
if (empty($audioFile)) { die("File not found."); }

if (!extension_loaded('fileinfo')) {
    if (function_exists('fatalConfigError')) {
        fatalConfigError($L['audio_single']['error_fileinfo']);
    } else {
        die($L['audio_single']['error_fileinfo_short']);
    }
}

$audioUrl = "index.php?download=" . rawurlencode($audioFile);
$fileName = $fileName ?? basename($audioFile);

global $config;
if (isset($config['base_dir'])) {
    $fullPath = rtrim($config['base_dir'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($audioFile, DIRECTORY_SEPARATOR);
    if (file_exists($fullPath)) {
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($fullPath);
        if (!str_starts_with($realMime, 'audio/')) {
            showMessage('File <b>' . htmlspecialchars($fileName) . '</b> is damaged or is not an audio file.', 'danger', 'Error');
            return;
        }
    }
}
?>
<style>
#audio-single-wrapper {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.85); display: flex; align-items: center;
    justify-content: center; z-index: 10008; backdrop-filter: blur(5px);
}
.audio-single-card {
    background: #fff; width: 95%; max-width: 500px;
    border-radius: 12px; box-shadow: 0 15px 40px rgba(0,0,0,0.5); overflow: hidden;
}
.audio-visual-container { padding: 20px; background: #000; margin: 10px 20px; border-radius: 10px; overflow: hidden; }
#winampVis { width: 100%; height: 160px; background: #000; display: block; image-rendering: pixelated; }
.seek-container { padding: 0 20px; margin-bottom: 10px; }
.audio-progress { width: 100%; cursor: pointer; height: 6px; -webkit-appearance: none; background: #ddd; border-radius: 3px; outline: none; }
.audio-progress::-webkit-slider-thumb { -webkit-appearance: none; width: 14px; height: 14px; background: #0d6efd; border-radius: 50%; cursor: pointer; }
.audio-controls { display: flex; align-items: center; gap: 15px; padding: 15px 20px; background: #f8f9fa; }
#time-info-final { min-width: 130px; font-variant-numeric: tabular-nums; white-space: nowrap; }
.audio-info-header { padding: 15px 20px; border-bottom: 1px solid #eee; background: #fcfcfc; }
.freq-scale { width: 420px; margin: -5px auto 10px auto; padding: 0; color: #008c00; font-family: "Courier New", Courier, monospace; font-size: 9px; font-weight: normal; letter-spacing: 0.5px; background: transparent; display: flex; justify-content: space-between; align-items: center; pointer-events: none; user-select: none; }
.freq-scale .tick { color: #008c00; opacity: 0.8; margin: 0 3px; font-size: 10px; }
</style>

<div id="audio-single-wrapper" onclick="FilePreview.close('audio-single-wrapper')">
    <div class="audio-single-card" onclick="event.stopPropagation()">
        <div class="audio-info-header d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark text-truncate me-3">
                <i class="fa-solid fa-file-audio text-primary me-2"></i><?= htmlspecialchars($fileName) ?>
            </span>
            <button class="btn-close" onclick="FilePreview.close('audio-single-wrapper')"></button>
        </div>
        <div class="audio-visual-container">
            <canvas id="winampVis" width="300" height="180"></canvas>
        </div>
        <div class="freq-scale">
            40Hz <span class="tick">|</span><span class="tick">|</span>
            1kHz <span class="tick">|</span><span class="tick">|</span> 16kHz
        </div>
        <div class="seek-container">
            <input type="range" id="seekSlider" class="audio-progress" value="0" min="0" step="0.1">
        </div>
        <div class="audio-controls">
            <button type="button" class="btn btn-primary btn-sm rounded-circle"
                    id="playBtnFinal" style="width: 45px; height: 45px;">
                <i class="fa-solid fa-play" id="playIconFinal"></i>
            </button>
            <div class="flex-grow-1 small text-muted fw-bold" id="time-info-final">0:00 / 0:00</div>
            <input type="range" id="volSliderFinal" min="0" max="1" step="0.05" value="0.7"
                   class="form-range" style="width: 80px;">
        </div>
        <div id="audio-js-error" class="alert alert-danger m-2 d-none small">
            <?= htmlspecialchars($L['audio_single']['error_format']) ?>
        </div>
        <div class="p-2 bg-light border-top text-end">
            <button class="btn btn-secondary btn-sm px-4"
                    onclick="FilePreview.close('audio-single-wrapper')"><?= htmlspecialchars($L['audio_single']['close']) ?></button>
        </div>
    </div>
</div>

<img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
     style="display:none;"
     onload="(function(){
    const CONFIG = { fftSize:4096,numBars:42,barWidth:7,barGap:2,segmentH:3,segmentG:1,peakHoldTime:25,gravity:0.15,minFreq:40,maxFreq:16000 };
    const audio    = new Audio();
    audio.src      = '<?= $audioUrl ?>';
    audio.crossOrigin = 'anonymous';
    let aCtx, analyser, data, peaks=[], peakHold=[], peakVel=[];
    const canvas   = document.getElementById('winampVis');
    const ctx      = canvas.getContext('2d');
    const playBtn  = document.getElementById('playBtnFinal');
    const playIcon = document.getElementById('playIconFinal');
    const slider   = document.getElementById('seekSlider');
    const tInfo    = document.getElementById('time-info-final');
    const errBox   = document.getElementById('audio-js-error');
    const wrapper  = document.getElementById('audio-single-wrapper');
    const formatTime = (s) => { if (isNaN(s)||s<0) return '0:00'; const h=Math.floor(s/3600),m=Math.floor((s%3600)/60),sec=Math.floor(s%60).toString().padStart(2,'0'); return h>0?h+':'+m.toString().padStart(2,'0')+':'+sec:m+':'+sec; };
    function getLogData(ld) { const out=new Uint8Array(CONFIG.numBars),ny=aCtx.sampleRate/2; for(let i=0;i<CONFIG.numBars;i++){const lo=CONFIG.minFreq*Math.pow(CONFIG.maxFreq/CONFIG.minFreq,i/CONFIG.numBars),hi=CONFIG.minFreq*Math.pow(CONFIG.maxFreq/CONFIG.minFreq,(i+1)/CONFIG.numBars),li=Math.floor(lo/ny*ld.length),hi2=Math.floor(hi/ny*ld.length);let s=0,c=0;for(let j=li;j<=hi2;j++){s+=ld[j];c++;}out[i]=c>0?s/c:0;}return out; }
    const render = () => { if(!aCtx||aCtx.state==='closed')return; requestAnimationFrame(render); analyser.getByteFrequencyData(data); const log=getLogData(data); ctx.fillStyle='#000'; ctx.fillRect(0,0,canvas.width,canvas.height); const step=CONFIG.barWidth+CONFIG.barGap,offX=(canvas.width-CONFIG.numBars*step)/2; for(let i=0;i<CONFIG.numBars;i++){let h=(log[i]/255)*(canvas.height-15),x=offX+i*step; if(h>=peaks[i]){peaks[i]=h;peakHold[i]=CONFIG.peakHoldTime;peakVel[i]=0;}else{if(peakHold[i]>0)peakHold[i]--;else{peakVel[i]+=CONFIG.gravity;peaks[i]-=peakVel[i];}}if(peaks[i]<0)peaks[i]=0; ctx.fillStyle='#fff';ctx.fillRect(x,canvas.height-peaks[i]-1,CONFIG.barWidth,1); for(let y=0;y<h;y+=(CONFIG.segmentH+CONFIG.segmentG)){let r=y/canvas.height;ctx.fillStyle=r<0.25?'#007700':(r<0.5?'#00ff00':(r<0.7?'#ffff00':'#ff0000'));ctx.fillRect(x,canvas.height-y-CONFIG.segmentH,CONFIG.barWidth,CONFIG.segmentH);}} };
    const startAudio = () => { if(!aCtx){try{aCtx=new(window.AudioContext||window.webkitAudioContext)();analyser=aCtx.createAnalyser();analyser.fftSize=CONFIG.fftSize;const src=aCtx.createMediaElementSource(audio);src.connect(analyser).connect(aCtx.destination);data=new Uint8Array(analyser.frequencyBinCount);peaks=new Array(CONFIG.numBars).fill(0);peakHold=new Array(CONFIG.numBars).fill(0);peakVel=new Array(CONFIG.numBars).fill(0);render();}catch(e){console.error(e);}} if(audio.paused){audio.play().then(()=>{playIcon.className='fa-solid fa-pause';errBox.classList.add('d-none');}).catch(err=>{errBox.classList.remove('d-none');console.warn(err);});}else{audio.pause();playIcon.className='fa-solid fa-play';} };
    if(wrapper){wrapper.addEventListener('preview:close',function(){audio.pause();audio.src='';if(aCtx&&aCtx.state!=='closed')aCtx.close().catch(()=>{});});}
    audio.onended=()=>{audio.currentTime=0;playIcon.className='fa-solid fa-play';if(slider)slider.value=0;};
    playBtn.onclick=startAudio;
    audio.ontimeupdate=()=>{if(!isNaN(audio.duration)){if(slider){slider.max=audio.duration;slider.value=audio.currentTime;}if(tInfo){tInfo.innerText=formatTime(audio.currentTime)+' / '+formatTime(audio.duration);}}};
    slider.oninput=(e)=>{audio.currentTime=e.target.value;};
    document.getElementById('volSliderFinal').oninput=(e)=>{audio.volume=e.target.value;};
    setTimeout(startAudio,100);
})()">