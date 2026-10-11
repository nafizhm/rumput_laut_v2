<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Masuk · Albagro Indo Sejahtera</title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;700;800&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{--bg:#f3f8f6;--card:#fff;--ink:#1d2a26;--muted:#62716b;--brand:#8b7048;--leaf:#2f8f4e;--leaf-d:#23703c;--line:#d9e5df;--chip:#e9f2ee;--err:#b3261e;
box-sizing:border-box;padding-top:env(safe-area-inset-top,0px);padding-bottom:env(safe-area-inset-bottom,0px)}
@media (prefers-color-scheme:dark){:root:not([data-theme="light"]){--bg:#0f1816;--card:#16231f;--ink:#e8f1ed;--muted:#97a8a1;--line:#2b3d37;--chip:#1f302a;--leaf:#4cc074;--leaf-d:#3aa65f}}
:root[data-theme="dark"]{--bg:#0f1816;--card:#16231f;--ink:#e8f1ed;--muted:#97a8a1;--line:#2b3d37;--chip:#1f302a;--leaf:#4cc074;--leaf-d:#3aa65f}
html{scroll-padding-top:env(safe-area-inset-top,0px)}
*,*:before,*:after{box-sizing:inherit}
body{margin:0;background:var(--bg);color:var(--ink);font:16px/1.5 Figtree,system-ui,-apple-system,"Segoe UI",sans-serif;min-height:100vh}
.wrap{display:grid;grid-template-columns:1.15fr 1fr;min-height:100vh}
/* panel ilustrasi */
.scene{position:relative;overflow:hidden;background:#0b4a5e;color:#fff;min-height:100%}
.scene img.art{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.scene .txt{position:relative;z-index:1;padding:clamp(24px,5vw,56px);display:flex;flex-direction:column;height:100%;justify-content:space-between}
.scene h2{font:800 clamp(1.7rem,3.2vw,2.8rem)/1.08 "Bricolage Grotesque",Figtree,sans-serif;margin:0;max-width:15ch;letter-spacing:-.02em;text-shadow:0 2px 18px rgba(4,40,52,.45)}
.scene p{max-width:38ch;margin:.8rem 0 0;color:#e3f4f2;text-shadow:0 1px 10px rgba(4,40,52,.5)}
.flow{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:0;padding:0;list-style:none}
.flow li{background:rgba(7,50,64,.62);backdrop-filter:blur(6px);border:1px solid rgba(255,255,255,.22);border-radius:999px;padding:7px 14px;font-weight:600;font-size:.9rem;display:flex;gap:8px;align-items:center}
.flow li svg{width:18px;height:18px;flex:none}
.flow i{width:18px;height:2px;background:rgba(255,255,255,.55);border-radius:2px}
/* form */
.panel{display:flex;align-items:center;justify-content:center;padding:clamp(20px,5vw,56px);position:relative}
.card{width:100%;max-width:420px}
.logo{display:inline-block;background:#fff;border-radius:16px;padding:10px 16px;margin-bottom:28px;box-shadow:0 1px 0 var(--line)}
.logo img{display:block;height:58px;width:auto;max-width:100%}
h1{font:800 2rem/1.1 "Bricolage Grotesque",Figtree,sans-serif;margin:0 0 6px;letter-spacing:-.02em}
.sub{margin:0 0 22px;color:var(--muted)}
fieldset{border:0;margin:0 0 18px;padding:0}
legend{font-weight:600;font-size:.9rem;padding:0;margin-bottom:8px}
.roles{display:grid;grid-template-columns:repeat(4,1fr);gap:6px}
.roles label{cursor:pointer}
.roles input{position:absolute;opacity:0;pointer-events:none}
.roles span{display:flex;flex-direction:column;align-items:center;gap:4px;padding:10px 4px;border:1.5px solid var(--line);border-radius:12px;font-size:.8rem;font-weight:600;background:var(--card);color:var(--muted);transition:border-color .15s,background .15s,color .15s}
.roles svg{width:22px;height:22px}
.roles input:checked+span{border-color:var(--leaf);background:var(--chip);color:var(--ink)}
.roles input:focus-visible+span{outline:3px solid var(--leaf);outline-offset:2px}
.f{margin-bottom:16px}
.f label{display:block;font-weight:600;font-size:.9rem;margin-bottom:6px}
.in{position:relative}
.in input{width:100%;font:inherit;color:var(--ink);background:var(--card);border:1.5px solid var(--line);border-radius:12px;padding:13px 14px;transition:border-color .15s,box-shadow .15s}
.in input:focus{outline:none;border-color:var(--leaf);box-shadow:0 0 0 4px color-mix(in srgb,var(--leaf) 22%,transparent)}
.in button{position:absolute;right:6px;top:50%;transform:translateY(-50%);background:none;border:0;color:var(--muted);font:inherit;font-size:.85rem;font-weight:600;padding:8px 10px;border-radius:8px;cursor:pointer}
.in button:hover{color:var(--ink)}
.in input.pw{padding-right:76px}
.row{display:flex;justify-content:space-between;align-items:center;gap:12px;margin:2px 0 20px;font-size:.92rem}
.row label{display:flex;gap:8px;align-items:center;cursor:pointer}
.row input{accent-color:var(--leaf);width:17px;height:17px}
a{color:var(--brand);font-weight:600;text-decoration:none}
a:hover{text-decoration:underline}
:root[data-theme="dark"] a{color:#d2b27c}
@media (prefers-color-scheme:dark){:root:not([data-theme="light"]) a{color:#d2b27c}}
.go{width:100%;font:600 1rem Figtree,sans-serif;color:#fff;background:var(--leaf-d);border:0;border-radius:12px;padding:14px;cursor:pointer;transition:filter .15s,transform .1s}
.go:hover{filter:brightness(1.08)}
.go:active{transform:translateY(1px)}
.go:focus-visible,a:focus-visible,.in button:focus-visible{outline:3px solid var(--leaf);outline-offset:2px}
.msg{min-height:1.4em;margin:12px 0 0;font-size:.9rem;color:var(--err)}
.msg.ok{color:var(--leaf)}
.help{margin:22px 0 0;text-align:center;color:var(--muted);font-size:.92rem}
.foot{position:absolute;bottom:16px;left:0;right:0;text-align:center;font-size:.8rem;color:var(--muted)}
@media (max-width:900px){
 .wrap{grid-template-columns:1fr}
 .scene{min-height:250px}
 .scene .txt{justify-content:flex-end;gap:14px}
 .scene p,.flow{display:none}
 .panel{align-items:flex-start;padding-bottom:56px}
}
@media (prefers-reduced-motion:no-preference){
 .wv{animation:sway 9s ease-in-out infinite alternate}
 .wv2{animation-duration:12s;animation-direction:alternate-reverse}
 @keyframes sway{to{transform:translateX(-24px)}}
}
</style>
</head>
<body>
<div class="wrap">
 <aside class="scene" aria-label="Ilustrasi budidaya rumput laut">
  <img class="art" src="{{ asset('images/auth/bg-rumput-laut.jpg') }}" alt="" aria-hidden="true">
  <div class="txt">
   <div>
    <h2>Dari tambak ke pabrik, semua tercatat.</h2>
    <p>Pantau panen, pengiriman, dan stok rumput laut dalam satu aplikasi.</p>
   </div>
   <ul class="flow" aria-label="Alur rumput laut">
    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 21c0-6 0-10 0-16M12 14c-4 0-6-3-6-6 4 0 6 2 6 6M12 11c3 0 5-2 5-5-3 0-5 2-5 5"/></svg>Petani</li><i></i>
    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7M7 19a1.5 1.5 0 100-.01M17 19a1.5 1.5 0 100-.01"/></svg>Pengepul</li><i></i>
    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21V10l6 4v-4l6 4V5h3v16zM3 21h18"/></svg>Pabrik</li>
   </ul>
  </div>
 </aside>

 <main class="panel">
  <div class="card">
   <div class="logo"><img src="{{ asset('images/auth/logo-albagro.png') }}" alt="Logo PT Albagro Indo Sejahtera"></div>
   <h1>Masuk ke akun Anda</h1>
   <p class="sub">Masuk untuk mengelola rantai pasok rumput laut.</p>
   <form id="f" method="POST" action="{{ route('login') }}">
    @csrf
    <div class="f"><label for="u">Username</label><div class="in"><input id="u" name="username" value="{{ old('username') }}" type="text" autocomplete="username" placeholder="Masukkan username" required></div></div>
    <div class="f"><label for="p">Kata sandi</label><div class="in"><input class="pw" id="p" name="password" type="password" autocomplete="current-password" placeholder="Masukkan kata sandi" required><button type="button" id="t" aria-controls="p">Lihat</button></div></div>
    <button class="go" type="submit">Masuk</button>
    <p class="msg" id="m" role="status" aria-live="polite">{{ $errors->first() }}</p>
   </form>
   <p class="help">Belum punya akun? <a href="#">Hubungi admin Albagro</a></p>
  </div>
  <div class="foot">© 2026 PT Albagro Indo Sejahtera</div>
 </main>
</div>
<script>
(function(){
 var p=document.getElementById('p'),t=document.getElementById('t');
 t.onclick=function(){var h=p.type==='password';p.type=h?'text':'password';t.textContent=h?'Sembunyikan':'Lihat'};
})();
</script>
</body>
</html>