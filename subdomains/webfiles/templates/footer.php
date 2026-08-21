<?php // templates/footer.php ?>
</div> <!-- End .container -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// --- 0. GLOBAL APPLICATION CONTROLLER ---
const App = {
    _ajaxHeaders: { 'X-Requested-With': 'XMLHttpRequest' },
    refresh: function(params = {}) {
        const urlParams = new URLSearchParams(window.location.search);
        const page      = params.page     || urlParams.get('page')   || '1';
        const q         = params.q !== undefined ? params.q
                        : (document.getElementById('searchInput')?.value || '');
        const perPage   = params.per_page || (document.getElementById('perPage')?.value || '20');
        const dir       = params.dir !== undefined ? params.dir : (urlParams.get('dir') || '');
        const sort      = params.sort  || urlParams.get('sort')  || localStorage.getItem('fm_sort')  || 'mtime';
        const order     = params.order || urlParams.get('order') || localStorage.getItem('fm_order') || 'desc';
        const fetchParams = new URLSearchParams({ ajax:'1', dir, page, q, per_page: perPage, sort, order });
        fetch(window.location.pathname + '?' + fetchParams.toString(), { headers: this._ajaxHeaders })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.html) {
                const container = document.getElementById('dynamic-content');
                if (container) {
                    container.innerHTML = data.html;
                    const displayParams = new URLSearchParams(fetchParams);
                    displayParams.delete('ajax');
                    window.history.pushState({}, '', window.location.pathname + '?' + displayParams.toString());
                    if (typeof updateCheckedCount === 'function') updateCheckedCount();
                }
            }
        })
        .catch(err => console.error('App.refresh error:', err));
    }
};
// --- 1. GLOBAL PREVIEW CONTROLLER ---
const FilePreview = {
    state:        { scale: 1, rotation: 0, posX: 0, posY: 0, isDragging: false },
    gallery:      [],
    galleryIndex: 0,
    close: function(id) {
        if (id === 'img-preview-wrapper') {
            if (FilePreview._galleryKeyHandler) {
                document.removeEventListener('keydown', FilePreview._galleryKeyHandler);
                delete FilePreview._galleryKeyHandler;
            }
            FilePreview.gallery      = [];
            FilePreview.galleryIndex = 0;
        }
        const el = document.getElementById(id);
        if (!el) return;
        el.dispatchEvent(new CustomEvent('preview:close'));
        el.style.opacity = '0';
        setTimeout(() => {
            el.remove();
            const url = new URL(window.location.href);
            url.searchParams.delete('ajax');
            url.searchParams.delete('download');
            window.history.pushState({}, '', url.pathname + url.search);
        }, 200);
        document.removeEventListener('keydown', this.handleEsc);
    },
    handleEsc: (e) => {
        if (e.key === 'Escape') {
            ['img-preview-wrapper','pdf-preview-wrapper','txt-preview-wrapper',
             'csv-preview-wrapper','video-preview-wrapper','audio-single-wrapper',
             'audio-player-wrapper','msg-modal-wrapper','rename-modal-wrapper']
            .forEach(id => FilePreview.close(id));
        }
    },
    initImg: function() {
        const img      = document.getElementById('preview-image');
        const viewport = document.getElementById('viewport');
        if (!img || !viewport) return;
        this.state = { scale: 1, rotation: 0, posX: 0, posY: 0, isDragging: false };
        document.addEventListener('keydown', this.handleEsc.bind(this));
        viewport.onwheel      = (e) => { e.preventDefault(); this.zoom(e.deltaY < 0 ? 1.15 : 0.85); };
        viewport.onmousedown  = (e) => { if (e.button !== 0) return; this.state.isDragging = true; this.state.startX = e.clientX - this.state.posX; this.state.startY = e.clientY - this.state.posY; viewport.classList.add('dragging'); };
        viewport.onmousemove  = (e) => { if (!this.state.isDragging) return; this.state.posX = e.clientX - this.state.startX; this.state.posY = e.clientY - this.state.startY; this.applyImg(); };
        viewport.onmouseup    = () => { this.state.isDragging = false; viewport.classList.remove('dragging'); };
        viewport.onmouseleave = () => { this.state.isDragging = false; viewport.classList.remove('dragging'); };
    },
    applyImg: function() {
        const img      = document.getElementById('preview-image');
        const zoomText = document.getElementById('zoom-percent');
        if (img) img.style.transform = `translate(${this.state.posX}px,${this.state.posY}px) rotate(${this.state.rotation}deg) scale(${this.state.scale})`;
        if (zoomText) zoomText.innerText = Math.round(this.state.scale * 100) + '%';
    },
    rotate: function() { this.state.rotation = (this.state.rotation + 90) % 360; this.applyImg(); },
    zoom:   function(f) { const n = this.state.scale * f; if (n >= 0.1 && n <= 10) { this.state.scale = n; this.applyImg(); } },
    reset:  function() { this.state = { scale: 1, rotation: 0, posX: 0, posY: 0 }; this.applyImg(); }
};

// --- CUSTOM DELETE CONFIRMATION DIALOG ---
const DeleteConfirm = {
    _callback: null,
    show: function(title, bodyHtml, onConfirm) {
        this._callback = onConfirm;
        document.getElementById('deleteConfirmTitle').textContent = title;
        document.getElementById('deleteConfirmBody').innerHTML    = bodyHtml;
        (bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'))
            || new bootstrap.Modal(document.getElementById('deleteConfirmModal'))).show();
    }
};
document.addEventListener('click', function(e) {
    if (!e.target.closest('#deleteConfirmOk')) return;
    bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'))?.hide();
    if (typeof DeleteConfirm._callback === 'function') {
        DeleteConfirm._callback();
        DeleteConfirm._callback = null;
    }
});

// --- 2. CHECKBOX STATE UPDATE ---
function updateCheckedCount() {
    const container     = document.getElementById('dynamic-content') || document;
    const allCheckBoxes = container.querySelectorAll('.file-check');
    const checkedBoxes  = container.querySelectorAll('.file-check:checked');
    const count         = checkedBoxes.length;
    const btnZip        = document.getElementById('btn-zip');
    const countEl       = document.getElementById('count');
    const btnPlay       = document.getElementById('btn-audioplayer');
    const btnDel        = document.getElementById('btn-delete-selected');
    if (btnZip)  btnZip.disabled = (count === 0);
    if (countEl) countEl.innerText = count;
    if (btnDel)  btnDel.disabled = (count === 0);
    if (btnPlay) {
        const audioExts = ['mp3','wav','ogg','flac','m4a','m4b','aac'];
        const hasAudio  = Array.from(allCheckBoxes).some(cb => audioExts.some(ext => cb.value.toLowerCase().endsWith('.' + ext)));
        if (hasAudio) { btnPlay.classList.remove('d-none'); btnPlay.disabled = false; }
        else            btnPlay.classList.add('d-none');
    }
}

// --- 3. CLICK EVENT DELEGATION ---
document.addEventListener('click', function(e) {
    // ZIP download
    const btnZip = e.target.closest('#btn-zip');
    if (btnZip) {
        const checkedBoxes = document.querySelectorAll('.file-check:checked');
        if (checkedBoxes.length > 0) {
            const form = document.createElement('form');
            form.method = 'POST'; form.action = 'index.php';
            checkedBoxes.forEach(cb => { const i = document.createElement('input'); i.type='hidden'; i.name='selected[]'; i.value=cb.value; form.appendChild(i); });
            const ai = document.createElement('input'); ai.type='hidden'; ai.name='action'; ai.value='download_selected'; form.appendChild(ai);
            document.body.appendChild(form); form.submit(); document.body.removeChild(form);
        }
    }
    // Listen button
    const playBtn = e.target.closest('#btn-audioplayer');
    if (playBtn) {
        e.preventDefault();
        const audioExts = ['mp3','wav','ogg','flac','m4a','m4b','aac'];
        const allAudio  = Array.from(document.querySelectorAll('.file-check'))
            .map(cb => cb.value)
            .filter(f => audioExts.includes(f.split('.').pop().toLowerCase()));
        if (allAudio.length > 0) {
            const params = new URLSearchParams({ ajax:'1', action:'get_preview', type:'audio_playlist' });
            allAudio.forEach(f => params.append('all_audio[]', f));
            fetch(window.location.pathname + '?' + params.toString(), { headers: App._ajaxHeaders })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.html) {
                    FilePreview.close('audio-player-wrapper');
                    setTimeout(() => {
                        const pw = document.createElement('div');
                        pw.id = 'audio-player-wrapper'; pw.innerHTML = data.html;
                        document.body.appendChild(pw);
                        pw.querySelectorAll('script').forEach(old => {
                            const ns = document.createElement('script');
                            Array.from(old.attributes).forEach(a => ns.setAttribute(a.name, a.value));
                            ns.appendChild(document.createTextNode(old.innerHTML));
                            old.parentNode.replaceChild(ns, old);
                        });
                    }, 250);
                }
            })
            .catch(err => console.error('playlist error:', err));
        }
        return;
    }
    // File preview
    const previewLink = e.target.closest('.preview-link');
    if (previewLink) {
        e.preventDefault();
        const file = previewLink.getAttribute('data-file');
        const type = (previewLink.getAttribute('data-type') || '').toLowerCase();
        const imgExts = ['jpg','jpeg','png','webp','gif','bmp','ico','svg'];
        if (imgExts.includes(type)) {
            const allImgLinks = Array.from(document.querySelectorAll('.preview-link'))
                .filter(a => imgExts.includes((a.getAttribute('data-type') || '').toLowerCase()));
            FilePreview.gallery      = allImgLinks.map(a => ({ file: a.getAttribute('data-file'), name: a.textContent.trim() }));
            FilePreview.galleryIndex = allImgLinks.findIndex(a => a.getAttribute('data-file') === file);
        } else {
            FilePreview.gallery = []; FilePreview.galleryIndex = 0;
        }
        const params = new URLSearchParams({ ajax:'1', action:'get_preview', file, type });
        fetch(window.location.pathname + '?' + params.toString(), { headers: App._ajaxHeaders })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.html) {
                document.getElementById('img-preview-wrapper')?.remove();
                ['pdf-preview-wrapper','txt-preview-wrapper','csv-preview-wrapper',
                 'video-preview-wrapper','audio-single-wrapper','audio-player-wrapper','msg-modal-wrapper']
                .forEach(id => FilePreview.close(id));
                document.body.insertAdjacentHTML('beforeend', data.html);
                if (data.html.includes('img-preview-wrapper')) FilePreview.initImg();
            }
        })
        .catch(err => console.error('preview error:', err));
    }
    // Folder navigation
    const dirLink = e.target.closest('.dir-link');
    if (dirLink) {
        e.preventDefault();
        const dir = decodeURIComponent(dirLink.getAttribute('data-dir') || '');
        const si  = document.getElementById('searchInput');
        if (si) si.value = '';
        window.history.pushState({}, '', window.location.pathname + '?' + new URLSearchParams({ dir }).toString());
        App.refresh({ page: '1', dir, q: '' });
        return;
    }
    // Pagination
    const pageLink = e.target.closest('.page-link');
    if (pageLink && pageLink.hasAttribute('data-page')) {
        e.preventDefault();
        App.refresh({ page: pageLink.getAttribute('data-page') });
    }
    // "Select all" checkbox
    if (e.target && e.target.id === 'check-all') {
        document.querySelectorAll('.file-check:not(:disabled)').forEach(cb => cb.checked = e.target.checked);
        updateCheckedCount();
    }
    if (e.target && e.target.classList.contains('file-check')) updateCheckedCount();
});

document.addEventListener('change',   e => { if (e.target?.id === 'perPage') App.refresh({ page: '1' }); });
document.addEventListener('click',    e => { if (e.target?.id === 'searchBtn') App.refresh({ page: '1' }); });
document.addEventListener('keypress', e => { if (e.key === 'Enter' && e.target?.id === 'searchInput') { e.preventDefault(); App.refresh({ page: '1' }); } });
document.addEventListener('input',    e => { if (e.target?.id === 'searchInput' && e.target.value === '') App.refresh({ q: '', page: '1' }); });

// --- IMAGE THUMBNAIL TOOLTIP (LRU) ---
(function() {
    const LRU_CAPACITY = 50;
    const _lruMap = new Map();
    const ThumbCache = {
        get(key) { if (!_lruMap.has(key)) return null; const url = _lruMap.get(key); _lruMap.delete(key); _lruMap.set(key, url); return url; },
        set(key, url) { if (_lruMap.has(key)) { _lruMap.delete(key); } else if (_lruMap.size >= LRU_CAPACITY) { const ok = _lruMap.keys().next().value; URL.revokeObjectURL(_lruMap.get(ok)); _lruMap.delete(ok); } _lruMap.set(key, url); },
        has(key) { return _lruMap.has(key); }
    };
    const tip = document.createElement('div');
    tip.id = 'img-thumb-tip';
    Object.assign(tip.style, { position:'fixed', zIndex:'9000', pointerEvents:'none', display:'none', background:'#fff', border:'1px solid #dee2e6', borderRadius:'10px', boxShadow:'0 4px 24px rgba(0,0,0,0.18)', padding:'5px', opacity:'0', transition:'opacity 0.15s ease', width:'234px', minHeight:'40px' });
    document.body.appendChild(tip);
    function makeSkeleton() {
        const sk = document.createElement('div');
        Object.assign(sk.style, { width:'220px', height:'130px', borderRadius:'6px', background:'linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%)', backgroundSize:'200% 100%', animation:'thumbSkeletonShimmer 1.2s infinite linear', display:'flex', alignItems:'center', justifyContent:'center' });
        sk.innerHTML = '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>';
        return sk;
    }
    if (!document.getElementById('thumb-skeleton-style')) {
        const st = document.createElement('style'); st.id = 'thumb-skeleton-style';
        st.textContent = '@keyframes thumbSkeletonShimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}';
        document.head.appendChild(st);
    }
    function positionTip() {
        const tw=tip.offsetWidth||234, th=tip.offsetHeight||40, vw=window.innerWidth, vh=window.innerHeight, GAP=16;
        let x=mouseX+GAP, y=mouseY+GAP;
        if (x+tw > vw-4) x = mouseX-tw-GAP;
        if (y+th > vh-4) y = mouseY-th-GAP;
        tip.style.left = Math.round(x)+'px'; tip.style.top = Math.round(y)+'px';
    }
    let mouseX=0, mouseY=0, currentRel=null, enterTimer=null, abortCtrl=null;
    document.addEventListener('mousemove', e => { mouseX=e.clientX; mouseY=e.clientY; if (tip.style.display!=='none') positionTip(); });
    function showTip(rel) {
        tip.innerHTML = '';
        const cached = ThumbCache.get(rel);
        if (cached) {
            const img = new Image(); img.style.cssText='display:block;max-width:220px;max-height:220px;border-radius:6px;object-fit:contain;'; img.src=cached;
            tip.appendChild(img); tip.style.display='block';
            requestAnimationFrame(() => { positionTip(); tip.style.opacity='1'; });
            return;
        }
        tip.appendChild(makeSkeleton()); tip.style.display='block'; tip.style.opacity='1';
        requestAnimationFrame(() => positionTip());
        if (abortCtrl) abortCtrl.abort();
        abortCtrl = new AbortController();
        const snap = rel;
        fetch(window.location.pathname+'?thumb='+encodeURIComponent(snap), { signal: abortCtrl.signal })
        .then(r => { if (!r.ok) throw new Error('HTTP '+r.status); return r.blob(); })
        .then(blob => {
            const url = URL.createObjectURL(blob); ThumbCache.set(snap, url);
            if (currentRel !== snap) return;
            const img = new Image(); img.style.cssText='display:block;max-width:220px;max-height:220px;border-radius:6px;object-fit:contain;';
            img.onload = () => { if (currentRel!==snap) return; tip.innerHTML=''; tip.appendChild(img); requestAnimationFrame(()=>positionTip()); };
            img.onerror = hideTip; img.src = url;
        })
        .catch(err => { if (err.name!=='AbortError') console.warn('[ThumbTip]',err.message); if (currentRel===snap) hideTip(); });
    }
    function hideTip() {
        if (abortCtrl) { abortCtrl.abort(); abortCtrl=null; }
        tip.style.opacity='0';
        setTimeout(() => { if (tip.style.opacity==='0') { tip.style.display='none'; tip.innerHTML=''; } }, 160);
        currentRel=null;
    }
    document.addEventListener('mouseover', e => {
        const link=e.target.closest('[data-thumb]'); if (!link) return;
        const rel=link.dataset.thumb; if (!rel||rel===currentRel) return;
        clearTimeout(enterTimer); if (currentRel) hideTip(); currentRel=rel;
        enterTimer=setTimeout(()=>{ if(currentRel===rel) showTip(rel); }, ThumbCache.has(rel)?0:160);
    });
    document.addEventListener('mouseout', e => {
        const link=e.target.closest('[data-thumb]'); if (!link) return;
        if (e.relatedTarget&&link.contains(e.relatedTarget)) return;
        if (link.dataset.thumb!==currentRel) return;
        clearTimeout(enterTimer); hideTip();
    });
    document.addEventListener('click', e => {
        if (!e.target.closest('[data-thumb]')) return;
        clearTimeout(enterTimer); if (abortCtrl){abortCtrl.abort();abortCtrl=null;}
        tip.style.display='none'; tip.style.opacity='0'; tip.innerHTML=''; currentRel=null;
    });
})();

// --- COLUMN SORTING ---
const FmSort = {
    _storageKey: 'fm_sort', _orderKey: 'fm_order',
    get() { return { sort: localStorage.getItem(this._storageKey)||'mtime', order: localStorage.getItem(this._orderKey)||'desc' }; },
    set(sort,order) { localStorage.setItem(this._storageKey,sort); localStorage.setItem(this._orderKey,order); }
};
document.addEventListener('click', e => {
    const th=e.target.closest('.sort-th'); if (!th) return;
    const newSort=th.dataset.sort, cur=FmSort.get();
    const newOrder=(cur.sort===newSort&&cur.order==='desc')?'asc':'desc';
    FmSort.set(newSort,newOrder); App.refresh({sort:newSort,order:newOrder,page:'1'});
});
document.addEventListener('DOMContentLoaded', () => updateCheckedCount());

// --- FILE RENAME ---
document.addEventListener('click', function(e) {
    const btn=e.target.closest('.btn-rename-file'); if (!btn) return;
    e.preventDefault();
    const params=new URLSearchParams({ajax:'1',action:'get_preview',file:btn.dataset.file,type:'rename'});
    fetch(window.location.pathname+'?'+params.toString(), {headers:App._ajaxHeaders})
    .then(r=>r.json())
    .then(data => { if (data.success&&data.html) { FilePreview.close('rename-modal-wrapper'); document.body.insertAdjacentHTML('beforeend',data.html); RenameModal.init(); } })
    .catch(err => console.error('rename open error:',err));
});
const RenameModal = {
    _badChars: /[\/\\:*?"<>|]/,
    init() {
        const input=document.getElementById('rename-input');
        const submitBtn=document.getElementById('rename-submit');
        const status=document.getElementById('rename-status');
        const hint=document.getElementById('rename-hint');
        if (!input||!submitBtn) return;
        const original=input.dataset.original||'', isDir=input.dataset.isDir==='1';
        const lastDot=!isDir?original.lastIndexOf('.'):-1;
        input.focus(); input.setSelectionRange(0, lastDot>0?lastDot:original.length);
        const validate=()=>{
            const val=input.value.trim(); status.className=''; status.innerHTML='';
            if (val===''||val.startsWith('.')) { hint.textContent=Lang.rename_modal.invalid; hint.className='form-text text-danger small mt-1'; submitBtn.disabled=true; return; }
            if (this._badChars.test(val)) { hint.textContent=Lang.rename_modal.invalid_chars; hint.className='form-text text-danger small mt-1'; submitBtn.disabled=true; return; }
            if (val===original) { hint.textContent=Lang.rename_modal.unchanged; hint.className='form-text text-muted small mt-1'; submitBtn.disabled=true; return; }
            hint.textContent=''; hint.className=''; submitBtn.disabled=false;
        };
        input.addEventListener('input',validate); validate();
        input.addEventListener('keydown', e => {
            if (e.key==='Enter'&&!submitBtn.disabled){e.preventDefault();submitBtn.click();}
            if (e.key==='Escape') FilePreview.close('rename-modal-wrapper');
        });
        submitBtn.addEventListener('click',()=>{
            const newName=input.value.trim(), fileRel=input.dataset.file;
            submitBtn.disabled=true; status.className=''; status.innerHTML='';
            const fd=new FormData(); fd.append('action','rename_file'); fd.append('file',fileRel); fd.append('new_name',newName);
            fetch(window.location.pathname+'?ajax=1',{method:'POST',headers:App._ajaxHeaders,body:fd})
            .then(r=>r.json())
            .then(data=>{
                if (data.success) { status.className='alert alert-success small py-1 px-2 mt-2'; status.innerHTML='<i class="fa-solid fa-check me-1"></i>'+data.message; setTimeout(()=>{FilePreview.close('rename-modal-wrapper');App.refresh({});},800); }
                else { status.className='alert alert-danger small py-1 px-2 mt-2'; status.innerHTML='<i class="fa-solid fa-triangle-exclamation me-1"></i>'+data.message; submitBtn.disabled=false; }
            })
            .catch(()=>{ status.className='alert alert-danger small py-1 px-2 mt-2'; status.textContent=Lang.upload_modal.network_error; submitBtn.disabled=false; });
        });
    }
};

// --- FOLDER CREATION ---
document.addEventListener('click', function(e) {
    const btn=e.target.closest('#btn-create-folder'); if (!btn) return;
    e.preventDefault();
    const input=document.getElementById('create-folder-input');
    const status=document.getElementById('create-folder-status');
    const submitBtn=document.getElementById('create-folder-submit');
    if (input) input.value='';
    if (status) { status.className='mt-2'; status.innerHTML=''; }
    if (submitBtn) submitBtn.disabled=false;
    (bootstrap.Modal.getInstance(document.getElementById('createFolderModal'))||new bootstrap.Modal(document.getElementById('createFolderModal'))).show();
    setTimeout(()=>input?.focus(),300);
});
document.addEventListener('click', function(e) {
    const btn=e.target.closest('#create-folder-submit'); if (!btn) return;
    const input=document.getElementById('create-folder-input');
    const status=document.getElementById('create-folder-status');
    const name=input?.value.trim()||'';
    if (!name) { status.className='mt-2 alert alert-warning small py-1 px-2'; status.textContent=Lang.folder_modal.empty; return; }
    if (/[\/\\:*?"<>|]/.test(name)||name.startsWith('.')) { status.className='mt-2 alert alert-warning small py-1 px-2'; status.textContent=Lang.folder_modal.invalid_chars; return; }
    btn.disabled=true;
    const fd=new FormData(); fd.append('action','create_folder'); fd.append('folder_name',name);
    fd.append('dir',new URLSearchParams(window.location.search).get('dir')||'');
    fetch(window.location.pathname+'?ajax=1',{method:'POST',headers:App._ajaxHeaders,body:fd})
    .then(r=>r.json())
    .then(data=>{
        if (data.success) { status.className='mt-2 alert alert-success small py-1 px-2'; status.innerHTML='<i class="fa-solid fa-check me-1"></i>'+data.message; setTimeout(()=>{bootstrap.Modal.getInstance(document.getElementById('createFolderModal'))?.hide();App.refresh({});},800); }
        else { status.className='mt-2 alert alert-danger small py-1 px-2'; status.innerHTML='<i class="fa-solid fa-triangle-exclamation me-1"></i>'+data.message; btn.disabled=false; }
    })
    .catch(()=>{ status.className='mt-2 alert alert-danger small py-1 px-2'; status.textContent=Lang.upload_modal.network_error; btn.disabled=false; });
});
document.addEventListener('keydown', e => { if (e.key==='Enter'&&e.target?.id==='create-folder-input'){e.preventDefault();document.getElementById('create-folder-submit')?.click();} });

// --- DELETE SELECTED FILES ---
document.addEventListener('click', function(e) {
    const btn=e.target.closest('#btn-delete-selected'); if (!btn) return;
    e.preventDefault();
    const checked=Array.from(document.querySelectorAll('.file-check:checked')); if (!checked.length) return;
    const names=checked.map(cb=>cb.closest('tr')?.querySelector('.preview-link')?.textContent.trim()||cb.value);
    const listHtml=names.slice(0,5).map(n=>'<li class="text-break">'+n.replace(/</g,'&lt;')+'</li>').join('')
        +(names.length>5?'<li class="text-muted">...+' +(names.length-5)+'</li>':'');
    DeleteConfirm.show(
        Lang.delete_modal.title_many.replace('{n}', checked.length),
        '<ul class="mb-2 ps-3 small" style="max-height:160px;overflow-y:auto;word-break:break-all;">'+listHtml+'</ul>'
        +'<p class="text-danger small mb-0"><i class="fa-solid fa-triangle-exclamation me-1"></i>'+Lang.delete_modal.irreversible+'</p>',
        function() {
            btn.disabled=true;
            const files=checked.map(cb=>cb.value); let completed=0, failed=[];
            files.forEach(file=>{
                const fd=new FormData(); fd.append('action','delete_file'); fd.append('file',file);
                fetch(window.location.pathname+'?ajax=1',{method:'POST',headers:App._ajaxHeaders,body:fd})
                .then(r=>r.json())
                .then(data=>{
                    if (data.success) { const cb=document.querySelector(`.file-check[value="${CSS.escape(file)}"]`); const row=cb?.closest('tr'); if(row){row.style.transition='opacity 0.3s';row.style.opacity='0';setTimeout(()=>row.remove(),300);} }
                    else failed.push(file);
                })
                .catch(()=>failed.push(file))
                .finally(()=>{
                    completed++;
                    if (completed===files.length) setTimeout(()=>{
                        updateCheckedCount();
                        if (failed.length) DeleteConfirm.show(Lang.delete_modal.error_title,'<p class="mb-0">'+Lang.delete_modal.error_many.replace('{n}',failed.length)+'</p>',null);
                    },350);
                });
            });
        }
    );
});

// --- DELETE FILE ---
document.addEventListener('click', function(e) {
    const btn=e.target.closest('.btn-delete-file'); if (!btn) return;
    e.preventDefault();
    const file=btn.dataset.file, name=btn.dataset.name;
    DeleteConfirm.show(
        Lang.delete_modal.title_one,
        '<p class="mb-1">'+Lang.delete_modal.file_label+'</p><p class="mb-0 text-break small" style="max-height:80px;overflow-y:auto;word-break:break-all;"><strong>'+name.replace(/</g,'&lt;')+'</strong></p>'
        +'<p class="text-danger small mb-0"><i class="fa-solid fa-triangle-exclamation me-1"></i>'+Lang.delete_modal.irreversible+'</p>',
        function() {
            const fd=new FormData(); fd.append('action','delete_file'); fd.append('file',file);
            fetch(window.location.pathname+'?ajax=1',{method:'POST',headers:App._ajaxHeaders,body:fd})
            .then(r=>r.json())
            .then(data=>{
                if (data.success) { const row=btn.closest('tr'); if(row){row.style.transition='opacity 0.3s';row.style.opacity='0';setTimeout(()=>{row.remove();updateCheckedCount();},300);} }
                else DeleteConfirm.show(Lang.delete_modal.error_title,'<p class="mb-0">'+data.message.replace(/<[^>]+>/g,'')+'</p>',null);
            })
            .catch(()=>DeleteConfirm.show(Lang.delete_modal.error_title,'<p class="mb-0">'+Lang.delete_modal.network_error+'</p>',null));
        }
    );
});

// --- FILE SIZE VALIDATION ON SELECT ---
document.addEventListener('change', function(e) {
    if (!e.target||e.target.id!=='upload-file-input') return;
    const input=e.target, submitBtn=document.getElementById('upload-submit'), status=document.getElementById('upload-status');
    const nameEl=document.getElementById('upload-file-name');
    const maxSizeRaw=input.dataset.maxSize||'';
    if (status){status.className='mt-3';status.innerHTML='';}
    if (submitBtn) submitBtn.disabled=false;
    // Show selected file name
    if (nameEl) nameEl.textContent = input.files.length ? input.files[0].name : '';
    if (!input.files.length||!maxSizeRaw||maxSizeRaw==='0') return;
    const m=maxSizeRaw.match(/^(\d+(?:\.\d+)?)\s*(g|gb|m|mb|k|kb|b)?$/i); if (!m) return;
    const num=parseFloat(m[1]), suffix=(m[2]||'b').toLowerCase();
    const maxBytes=suffix.startsWith('g')?num*1073741824:suffix.startsWith('m')?num*1048576:suffix.startsWith('k')?num*1024:num;
    if (input.files[0].size>maxBytes) {
        if (status){status.className='mt-3 alert alert-danger small';status.innerHTML='<i class="fa-solid fa-triangle-exclamation me-1"></i>'+Lang.upload_modal.too_large.replace('{size}',maxSizeRaw);}
        if (submitBtn) submitBtn.disabled=true;
        if (nameEl) nameEl.textContent='';
        input.value='';
    }
});
// Drag-and-drop for upload zone
(function(){
    const zone=document.getElementById('upload-drop-zone');
    if (!zone) return;
    zone.addEventListener('dragover',  e=>{e.preventDefault();zone.classList.add('dragover');});
    zone.addEventListener('dragleave', e=>{zone.classList.remove('dragover');});
    zone.addEventListener('drop', e=>{
        e.preventDefault(); zone.classList.remove('dragover');
        const files=e.dataTransfer?.files;
        if (!files||!files.length) return;
        const input=document.getElementById('upload-file-input');
        // Pass file to input via DataTransfer
        const dt=new DataTransfer(); dt.items.add(files[0]); input.files=dt.files;
        input.dispatchEvent(new Event('change', {bubbles:true}));
    });
})();

// --- FILE UPLOAD ---
document.addEventListener('click', function(e) {
    const btn=e.target.closest('#btn-upload'); if (!btn) return;
    e.preventDefault();
    const input=document.getElementById('upload-file-input');
    const status=document.getElementById('upload-status');
    const progress=document.getElementById('upload-progress');
    const submitBtn=document.getElementById('upload-submit');
    if (input) input.value='';
    if (status){status.className='mt-3';status.innerHTML='';}
    if (progress){progress.classList.add('d-none');progress.querySelector('.progress-bar').style.width='0%';}
    if (submitBtn) submitBtn.disabled=false;
    const nameEl=document.getElementById('upload-file-name');
    if (nameEl) nameEl.textContent='';
    const maxSize=btn.dataset.maxSize||'';
    const sizeHint=document.getElementById('upload-max-size-hint');
    const sizeVal=document.getElementById('upload-max-size-val');
    if (sizeHint&&sizeVal){if(maxSize&&maxSize!=='0'){sizeVal.textContent=maxSize;sizeHint.style.display='';}else sizeHint.style.display='none';}
    const fileInput=document.getElementById('upload-file-input');
    if (fileInput) fileInput.dataset.maxSize=maxSize;
    (bootstrap.Modal.getInstance(document.getElementById('uploadModal'))||new bootstrap.Modal(document.getElementById('uploadModal'))).show();
});
document.addEventListener('click', function(e) {
    const btn=e.target.closest('#upload-submit'); if (!btn) return;
    const input=document.getElementById('upload-file-input');
    if (!input||!input.files.length){const s=document.getElementById('upload-status');s.className='mt-3 alert alert-warning small';s.innerHTML=Lang.upload_modal.no_file;return;}
    const fd=new FormData(); fd.append('action','upload_file'); fd.append('upload_file',input.files[0]);
    fd.append('dir',new URLSearchParams(window.location.search).get('dir')||'');
    const status=document.getElementById('upload-status');
    const progress=document.getElementById('upload-progress');
    const progressBar=progress.querySelector('.progress-bar');
    status.className=''; status.innerHTML='';
    progress.classList.remove('d-none'); progressBar.style.width='0%';
    btn.disabled=true;
    const xhr=new XMLHttpRequest();
    xhr.open('POST',window.location.pathname+'?ajax=1',true);
    xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
    xhr.upload.onprogress=e=>{if(e.lengthComputable){const pct=Math.round((e.loaded/e.total)*100);progressBar.style.width=pct+'%';progressBar.textContent=pct+'%';}};
    xhr.onload=()=>{
        progress.classList.add('d-none');
        try {
            const data=JSON.parse(xhr.responseText);
            if(data.success){status.className='mt-3 alert alert-success small';status.innerHTML='<i class="fa-solid fa-check me-1"></i>'+data.message;setTimeout(()=>{bootstrap.Modal.getInstance(document.getElementById('uploadModal'))?.hide();App.refresh({});},1000);}
            else{status.className='mt-3 alert alert-danger small';status.innerHTML='<i class="fa-solid fa-triangle-exclamation me-1"></i>'+data.message;btn.disabled=false;}
        } catch{status.className='mt-3 alert alert-danger small';status.innerHTML=Lang.upload_modal.server_error;btn.disabled=false;}
    };
    xhr.onerror=()=>{progress.classList.add('d-none');status.className='mt-3 alert alert-danger small';status.innerHTML=Lang.upload_modal.network_error;btn.disabled=false;};
    xhr.send(fd);
});
</script>

<!-- Upload file modal -->
<style>
.upload-drop-zone {
    border: 2px dashed var(--fm-border, #ced4da);
    border-radius: 8px;
    padding: 28px 20px;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
    background: var(--fm-surface-3, #f8f9fa);
    color: var(--fm-text-muted, #6c757d);
    position: relative;
}
.upload-drop-zone:hover,
.upload-drop-zone.dragover {
    border-color: #0d6efd;
    background: rgba(13,110,253,0.05);
    color: var(--fm-text, #212529);
}
.upload-drop-zone input[type="file"] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
}
.upload-file-name {
    margin-top: 10px; font-size: 0.85rem;
    color: var(--fm-text, #212529);
    word-break: break-all; min-height: 1.2em;
}
</style>
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-upload me-2 text-primary"></i><?= htmlspecialchars($L['upload_modal']['title']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small text-muted fw-bold"><?= htmlspecialchars($L['upload_modal']['label']) ?></label>
                <!-- Custom file picker / drag-and-drop zone -->
                <div class="upload-drop-zone" id="upload-drop-zone">
                    <input type="file" id="upload-file-input" tabindex="-1">
                    <i class="fa-solid fa-cloud-arrow-up fa-2x mb-2 d-block"></i>
                    <span id="upload-drop-label"><?= htmlspecialchars($L['upload_modal']['drop_label'] ?? 'Click or drag a file here') ?></span>
                </div>
                <div class="upload-file-name text-center" id="upload-file-name"></div>
                <div id="upload-max-size-hint" class="form-text text-muted small mt-1" style="display:none;">
                    <?= htmlspecialchars($L['upload_modal']['max_size']) ?> <span id="upload-max-size-val"></span>
                </div>
                <div id="upload-progress" class="progress mt-3 d-none" style="height: 20px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;">0%</div>
                </div>
                <div id="upload-status" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= htmlspecialchars($L['upload_modal']['btn_cancel']) ?></button>
                <button type="button" class="btn btn-primary" id="upload-submit">
                    <i class="fa-solid fa-upload me-1"></i><?= htmlspecialchars($L['upload_modal']['btn_upload']) ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Create folder modal -->
<div class="modal fade" id="createFolderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-folder-plus me-2 text-primary"></i><?= htmlspecialchars($L['folder_modal']['title']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small text-muted fw-bold"><?= htmlspecialchars($L['folder_modal']['label']) ?></label>
                <input type="text" class="form-control" id="create-folder-input" placeholder="<?= htmlspecialchars($L['folder_modal']['placeholder']) ?>">
                <div id="create-folder-status" class="mt-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= htmlspecialchars($L['folder_modal']['btn_cancel']) ?></button>
                <button type="button" class="btn btn-primary" id="create-folder-submit">
                    <i class="fa-solid fa-folder-plus me-1"></i><?= htmlspecialchars($L['folder_modal']['btn_create']) ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-trash-can me-2 text-danger"></i><span id="deleteConfirmTitle"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="deleteConfirmBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= htmlspecialchars($L['delete_modal']['btn_cancel']) ?></button>
                <button type="button" class="btn btn-danger" id="deleteConfirmOk">
                    <i class="fa-solid fa-trash-can me-1"></i><?= htmlspecialchars($L['delete_modal']['btn_delete']) ?>
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Footer -->
<footer class="text-center py-3 mt-2" style="font-size:0.82rem; color: var(--fm-text-muted, #6c757d); border-top: 1px solid var(--fm-border, #dee2e6);">
    <div>
        Powered by
        <a href="https://github.com/CaPusto/WebFilesDesk"
           target="_blank" rel="noopener noreferrer"
           class="fw-bold text-decoration-none ms-1"
           style="color: var(--fm-link, #0d6efd);">WebFilesDesk</a>
    </div>
    <div class="mt-1 d-flex align-items-center justify-content-center gap-2">
        <a href="https://github.com/CaPusto/WebFilesDesk"
           target="_blank" rel="noopener noreferrer"
           style="color: var(--fm-text-muted, #6c757d);">
            <i class="fa-brands fa-github" style="font-size:1.1rem;"></i>
        </a>
        <span>&copy; <a href="mailto:dudorov@gmail.com"
              style="color: var(--fm-text-muted, #6c757d); text-decoration:none;">dudorov@gmail.com</a></span>
        <span><?= date('Y') ?></span>
    </div>
</footer>
</body>
</html>