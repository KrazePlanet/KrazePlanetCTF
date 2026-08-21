<?php
/**
 * templates/audio_player.php
 */
if (empty($playlist)) { die("Playlist is empty."); }
usort($playlist, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
?>
<style>
#audio-player-wrapper { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10009; backdrop-filter: blur(4px); }
.player-container { background: #fff; width: 920px; max-width: 95vw; height: 480px; border-radius: 15px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.4); }
.player-header { padding: 12px 25px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f0f0f0; background: #fff; }
.player-body { display: flex; flex: 1; overflow: hidden; }
.player-left { flex: 1.3; padding: 15px 30px; display: flex; flex-direction: column; background: #fff; }
.vis-window { background: #000; height: 180px; border-radius: 12px; position: relative; overflow: hidden; }
#playerVis { width: 100%; height: 100%; display: block; }
.freq-scale { display: flex; justify-content: space-between; padding: 5px 10px 15px 10px; font-size: 10px; color: #28a745; font-weight: bold; }
.p-progress { width: 100%; cursor: pointer; height: 4px; appearance: none; background: #eee; border-radius: 2px; margin-bottom: 10px; outline: none; }
.p-progress::-webkit-slider-thumb { appearance: none; width: 12px; height: 12px; background: #0d6efd; border-radius: 50%; border: 2px solid #fff; }
.modes-row { display: flex; gap: 25px; justify-content: center; margin-bottom: 5px; align-items: center; }
.mode-item { display: flex; flex-direction: column; align-items: center; width: 35px; }
.mode-dot { width: 5px; height: 5px; background: transparent; border-radius: 50%; margin-bottom: 4px; transition: 0.2s; }
.btn-mode { background: none; border: none; color: #bbb; font-size: 1.1rem; cursor: pointer; padding: 0; transition: 0.2s; line-height: 1; }
.mode-item.active .btn-mode { color: #0d6efd; }
.mode-item.active .mode-dot { background: #0d6efd; box-shadow: 0 0 5px rgba(13,110,253,0.5); }
.controls-row { display: flex; align-items: center; justify-content: space-between; margin-top: auto; padding-bottom: 5px; }
.btn-play-blue { width: 50px; height: 50px; background: #0d6efd; border: none; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(13,110,253,0.3); cursor: pointer; }
.btn-nav-small { background: none; border: none; color: #777; font-size: 1.1rem; cursor: pointer; }
.time-text { font-size: 0.85rem; color: #666; min-width: 90px; text-align: center; }
.player-right { width: 340px; background: #f9f9f9; border-left: 1px solid #eee; display: flex; flex-direction: column; }
.playlist-title { padding: 12px 20px; border-bottom: 1px solid #eee; font-weight: bold; font-size: 0.85rem; color: #0d6efd; cursor: pointer; user-select: none; }
.playlist-scroll { flex: 1; overflow-y: auto; }
.playlist-item { padding: 10px 20px; font-size: 0.8rem; border-bottom: 1px solid #f0f0f0; cursor: pointer; color: #555; display: flex; align-items: center; }
.playlist-item.active { background: #e7f1ff; color: #0d6efd; font-weight: bold; border-left: 4px solid #0d6efd; }
.playlist-item.error  { color: #aaa; text-decoration: line-through; }
.btn-footer-close { background: #6c757d; color: #fff; border: none; padding: 6px 18px; border-radius: 6px; font-size: 0.85rem; align-self: flex-end; margin-top: 10px; cursor: pointer; }
</style>

<div id="audio-player-wrapper" onclick="FilePreview.close('audio-player-wrapper')">
<div class="player-container" onclick="event.stopPropagation()">
    <div class="player-header">
        <div class="d-flex align-items-center gap-2 text-truncate fw-bold small">
            <i class="fa-solid fa-file-audio text-primary"></i>
            <span id="cur-track-name"><?= htmlspecialchars($L['audio_player']['loading']) ?></span>
        </div>
        <button type="button" class="btn-close" onclick="FilePreview.close('audio-player-wrapper')"></button>
    </div>
    <div class="player-body">
        <div class="player-left">
            <div class="vis-window"><canvas id="playerVis"></canvas></div>
            <div class="freq-scale">
                40Hz <span class="tick">|</span><span class="tick">|</span>
                1kHz <span class="tick">|</span><span class="tick">|</span> 16kHz
            </div>
            <input type="range" id="pSeek" class="p-progress" value="0" min="0" max="100" step="0.1">
            <div class="modes-row">
                <div class="mode-item" id="item-repeatAll"><div class="mode-dot"></div><button class="btn-mode" id="mRepeatAll" title="<?= htmlspecialchars($L['audio_player']['repeat_all']) ?>"><i class="fa-solid fa-repeat"></i></button></div>
                <div class="mode-item" id="item-repeatOne"><div class="mode-dot"></div><button class="btn-mode" id="mRepeatOne" title="<?= htmlspecialchars($L['audio_player']['repeat_one']) ?>"><i class="fa-solid fa-arrows-rotate"></i></button></div>
                <div class="mode-item" id="item-shuffle"><div class="mode-dot"></div><button class="btn-mode" id="mShuffle" title="<?= htmlspecialchars($L['audio_player']['shuffle']) ?>"><i class="fa-solid fa-shuffle"></i></button></div>
            </div>
            <div class="controls-row">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn-nav-small" id="pPrev"><i class="fa-solid fa-backward-step"></i></button>
                    <button class="btn-play-blue shadow-sm" id="pPlay"><i class="fa-solid fa-play fa-lg" id="pIcon"></i></button>
                    <button class="btn-nav-small" id="pNext"><i class="fa-solid fa-forward-step"></i></button>
                    <div class="time-text" id="pTime" style="min-width:130px;font-variant-numeric:tabular-nums;">0:00 / 0:00</div>
                </div>
                <div class="d-flex align-items: center gap-2" style="width: 110px;">
                    <i class="fa-solid fa-volume-low text-muted small"></i>
                    <input type="range" id="pVol" class="p-progress" min="0" max="1" step="0.05" value="0.7">
                </div>
            </div>
            <button class="btn-footer-close" onclick="FilePreview.close('audio-player-wrapper')"><?= htmlspecialchars($L['audio_player']['close']) ?></button>
        </div>
        <div class="player-right">
            <div class="playlist-title d-flex justify-content-between x-small" id="pSortTrigger">
                <span><i class="fa-solid fa-list-ul me-2"></i><?= htmlspecialchars($L['audio_player']['playlist']) ?> <i id="pSortIcon" class="fa-solid fa-arrow-down-a-z ms-1"></i></span>
                <span class="badge bg-light text-dark border fw-normal" id="pCount">0 / 0</span>
            </div>
            <div class="playlist-scroll" id="pItems">
                <?php foreach($playlist as $idx => $track): ?>
                <div class="playlist-item" data-idx="<?= $idx ?>" data-url="<?= htmlspecialchars($track['url']) ?>" data-name="<?= htmlspecialchars($track['name']) ?>">
                    <span class="text-muted me-2 small index-label"><?= ($idx + 1) ?>.</span>
                    <span class="track-text text-truncate"><?= htmlspecialchars($track['name']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
</div>

<script>
(function() {
    const playlistData = <?php echo json_encode($playlist); ?>;
    let modes = { repeatAll: false, repeatOne: false, shuffle: false };
    let isAsc = true, isPlaying = false, isSeeking = false, isDestroyed = false, isClosing = false;
    let skipErrors = new Set();
    const audio = new Audio(); audio.volume = 0.7; audio.preload = 'metadata';
    let aCtx, analyser, visData, animId, peaks=[], peakHold=[], peakVel=[];
    const VIS = { numBars:42,barWidth:7,barGap:2,segH:3,segG:1,peakHold:25,gravity:0.15,minFreq:40,maxFreq:16000 };
    const pItems=document.getElementById('pItems'), pIcon=document.getElementById('pIcon'), pTime=document.getElementById('pTime');
    const pSeek=document.getElementById('pSeek'), pCount=document.getElementById('pCount'), pVol=document.getElementById('pVol');
    const canvas=document.getElementById('playerVis'), ctx=canvas?canvas.getContext('2d'):null;

    function getDomRows() { return Array.from(pItems.querySelectorAll('.playlist-item')); }
    function getActiveRow() { return pItems.querySelector('.playlist-item.active'); }
    function setActiveRow(row) {
        getDomRows().forEach(r => r.classList.remove('active')); row.classList.add('active');
        row.scrollIntoView({ block: 'nearest' });
        const rows = getDomRows(); pCount.textContent = (rows.indexOf(row)+1)+' / '+rows.length;
        document.getElementById('cur-track-name').textContent = row.dataset.name;
    }
    function playRow(row) {
        if (!row) return; setActiveRow(row); audio.src = row.dataset.url; audio.load();
        audio.play().then(() => { isPlaying=true; pIcon.className='fa-solid fa-pause fa-lg'; initVisualizer(); })
        .catch(err => { if(err.name!=='AbortError'){console.warn('Play error:',err);markError(row);playNext();} });
    }
    function markError(row) { row.classList.add('error'); skipErrors.add(row.dataset.url); }
    function playNext() {
        const rows=getDomRows().filter(r=>!r.classList.contains('error')); if(!rows.length) return;
        const active=getActiveRow(), curPos=rows.indexOf(active);
        if(modes.repeatOne){playRow(active||rows[0]);return;}
        if(modes.shuffle){let rnd;do{rnd=Math.floor(Math.random()*rows.length);}while(rows.length>1&&rows[rnd]===active);playRow(rows[rnd]);return;}
        const next=rows[curPos+1];
        if(next){playRow(next);}else if(modes.repeatAll){playRow(rows[0]);}
        else{isPlaying=false;pIcon.className='fa-solid fa-play fa-lg';audio.pause();audio.currentTime=0;setActiveRow(rows[0]);audio.src=rows[0].dataset.url;}
    }
    function playPrev() { const rows=getDomRows().filter(r=>!r.classList.contains('error')),active=getActiveRow(),curPos=rows.indexOf(active),prev=rows[curPos-1]||rows[rows.length-1];playRow(prev); }

    audio.onended=()=>{if(!isDestroyed)playNext();};
    audio.onerror=()=>{if(isClosing||isDestroyed)return;const active=getActiveRow();if(active){console.warn('Audio error:',active.dataset.name);markError(active);}setTimeout(()=>{if(!isDestroyed)playNext();},300);};
    audio.ontimeupdate=()=>{if(isSeeking||isNaN(audio.duration))return;pSeek.value=(audio.currentTime/audio.duration)*100;pTime.textContent=fmt(audio.currentTime)+' / '+fmt(audio.duration);};
    audio.onpause=()=>{if(animId){cancelAnimationFrame(animId);animId=null;}};
    audio.onplay=()=>{if(aCtx&&!animId)renderVis();};

    document.getElementById('pPlay').onclick=()=>{
        if(isPlaying){audio.pause();isPlaying=false;pIcon.className='fa-solid fa-play fa-lg';}
        else{const active=getActiveRow()||getDomRows()[0];if(!audio.src||audio.src===window.location.href){playRow(active);}else{audio.play().then(()=>{isPlaying=true;pIcon.className='fa-solid fa-pause fa-lg';initVisualizer();}).catch(()=>{});}}
    };
    document.getElementById('pNext').onclick=()=>playNext();
    document.getElementById('pPrev').onclick=()=>playPrev();
    pSeek.oninput=()=>{isSeeking=true;};
    pSeek.onchange=()=>{if(!isNaN(audio.duration))audio.currentTime=(pSeek.value/100)*audio.duration;isSeeking=false;};
    pVol.oninput=()=>{audio.volume=pVol.value;};
    pItems.onclick=(e)=>{const row=e.target.closest('.playlist-item');if(row&&!row.classList.contains('error'))playRow(row);};

    function setMode(key){const was=modes[key];Object.keys(modes).forEach(k=>{modes[k]=false;document.getElementById('item-'+k).classList.remove('active');});if(!was){modes[key]=true;document.getElementById('item-'+key).classList.add('active');}}
    document.getElementById('mRepeatAll').onclick=()=>setMode('repeatAll');
    document.getElementById('mRepeatOne').onclick=()=>setMode('repeatOne');
    document.getElementById('mShuffle').onclick=()=>setMode('shuffle');

    document.getElementById('pSortTrigger').onclick=function(){
        const rows=getDomRows(); isAsc=!isAsc;
        rows.sort((a,b)=>isAsc?a.dataset.name.toLowerCase().localeCompare(b.dataset.name.toLowerCase(),undefined,{numeric:true}):b.dataset.name.toLowerCase().localeCompare(a.dataset.name.toLowerCase(),undefined,{numeric:true}));
        pItems.innerHTML=''; rows.forEach((el,i)=>{el.querySelector('.index-label').textContent=(i+1)+'.';pItems.appendChild(el);});
        document.getElementById('pSortIcon').className=isAsc?'fa-solid fa-arrow-down-a-z ms-1':'fa-solid fa-arrow-up-z-a ms-1';
        const active=getActiveRow(); if(active)active.scrollIntoView({block:'nearest'});
    };

    function initVisualizer(){if(!canvas||!ctx)return;if(aCtx){if(aCtx.state==='suspended')aCtx.resume();if(animId)return;}else{try{aCtx=new(window.AudioContext||window.webkitAudioContext)();analyser=aCtx.createAnalyser();analyser.fftSize=4096;const src=aCtx.createMediaElementSource(audio);src.connect(analyser);analyser.connect(aCtx.destination);const bc=analyser.frequencyBinCount;visData=new Uint8Array(bc);peaks=new Array(VIS.numBars).fill(0);peakHold=new Array(VIS.numBars).fill(0);peakVel=new Array(VIS.numBars).fill(0);}catch(e){console.warn('Visualizer:',e);return;}}renderVis();}
    function getLogData(){const ny=aCtx.sampleRate/2,out=new Uint8Array(VIS.numBars);for(let i=0;i<VIS.numBars;i++){const lo=VIS.minFreq*Math.pow(VIS.maxFreq/VIS.minFreq,i/VIS.numBars),hi=VIS.minFreq*Math.pow(VIS.maxFreq/VIS.minFreq,(i+1)/VIS.numBars),li=Math.floor(lo/ny*visData.length),hi2=Math.floor(hi/ny*visData.length);let s=0,c=0;for(let j=li;j<=hi2;j++){s+=visData[j];c++;}out[i]=c>0?s/c:0;}return out;}
    function renderVis(){if(!aCtx||aCtx.state==='closed'){animId=null;return;}animId=requestAnimationFrame(renderVis);analyser.getByteFrequencyData(visData);const log=getLogData();ctx.fillStyle='#000';ctx.fillRect(0,0,canvas.width,canvas.height);const step=VIS.barWidth+VIS.barGap,offX=(canvas.width-VIS.numBars*step)/2;for(let i=0;i<VIS.numBars;i++){const h=(log[i]/255)*(canvas.height-15),x=offX+i*step;if(h>=peaks[i]){peaks[i]=h;peakHold[i]=VIS.peakHold;peakVel[i]=0;}else{if(peakHold[i]>0)peakHold[i]--;else{peakVel[i]+=VIS.gravity;peaks[i]-=peakVel[i];}}if(peaks[i]<0)peaks[i]=0;ctx.fillStyle='#fff';ctx.fillRect(x,canvas.height-peaks[i]-1,VIS.barWidth,1);for(let y=0;y<h;y+=(VIS.segH+VIS.segG)){const r=y/canvas.height;ctx.fillStyle=r<0.25?'#007700':(r<0.5?'#00ff00':(r<0.7?'#ffff00':'#ff0000'));ctx.fillRect(x,canvas.height-y-VIS.segH,VIS.barWidth,VIS.segH);}}}

    document.getElementById('audio-player-wrapper').addEventListener('preview:close',()=>{isClosing=true;isDestroyed=true;audio.pause();audio.src='';if(animId){cancelAnimationFrame(animId);animId=null;}if(aCtx&&aCtx.state!=='closed')aCtx.close().catch(()=>{});});

    function fmt(s){if(isNaN(s)||s<0)return'0:00';const h=Math.floor(s/3600),m=Math.floor((s%3600)/60),sec=Math.floor(s%60);return h>0?h+':'+String(m).padStart(2,'0')+':'+String(sec).padStart(2,'0'):m+':'+String(sec).padStart(2,'0');}

    setTimeout(()=>{const rows=getDomRows();if(rows.length>0)playRow(rows[0]);},300);
})();
</script>