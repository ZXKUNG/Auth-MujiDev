<?php /* index.php — Demo Login/Register (SQLite + Tailwind) */ ?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Auth Demo — PHP + SQLite</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <meta name="color-scheme" content="dark light">
  <script>
    // Tailwind config (optional)
    tailwind.config = {
      theme: {
        extend: {
          colors: { brand: { 500: '#7c3aed', 600: '#6d28d9' } }
        }
      }
    }
  </script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
  <div class="max-w-4xl mx-auto p-4 md:p-8">
    <header class="flex items-center justify-between mb-6">
      <h1 class="text-xl md:text-2xl font-semibold tracking-tight">Auth Demo — PHP + SQLite</h1>
      <a href="admin.php" class="inline-flex items-center gap-2 text-sm px-3 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 ring-1 ring-slate-700">
        เปิดหน้า Admin
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M13 7h5v5h-2V9.41l-8.29 8.3-1.42-1.42 8.3-8.29H13z"/></svg>
      </a>
    </header>

    <div class="grid md:grid-cols-2 gap-6">
      <!-- Card -->
      <div class="col-span-2 md:col-span-1">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 shadow-lg backdrop-blur">
          <div class="p-4 md:p-6">
            <div class="flex gap-2 mb-4">
              <button id="tabLogin" class="px-4 py-2 rounded-xl text-sm font-medium bg-brand-600 hover:bg-brand-500">
                Login
              </button>
              <button id="tabRegister" class="px-4 py-2 rounded-xl text-sm font-medium bg-slate-800 hover:bg-slate-700">
                Register
              </button>
            </div>

            <!-- Login -->
            <form id="loginForm" class="space-y-4">
              <div>
                <label class="text-sm text-slate-300">Email</label>
                <input type="email" name="email" required placeholder="you@example.com"
                  class="mt-1 w-full rounded-xl bg-slate-950/60 border border-slate-700 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-600" />
              </div>
              <div>
                <label class="text-sm text-slate-300">Password</label>
                <input type="password" name="password" required placeholder="••••••••"
                  class="mt-1 w-full rounded-xl bg-slate-950/60 border border-slate-700 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-600" />
              </div>
              <button type="submit"
                class="w-full inline-flex justify-center items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-500 px-4 py-2 font-semibold">
                เข้าสู่ระบบ
              </button>
              <p class="text-xs text-slate-400">POST <code>api.php?action=login</code></p>
            </form>

            <!-- Register -->
            <form id="registerForm" class="space-y-4 hidden">
              <div>
                <label class="text-sm text-slate-300">Email</label>
                <input type="email" name="email" required placeholder="you@example.com"
                  class="mt-1 w-full rounded-xl bg-slate-950/60 border border-slate-700 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-600" />
              </div>
              <div>
                <label class="text-sm text-slate-300">Username (ไม่บังคับ)</label>
                <input type="text" name="username" minlength="2" maxlength="32" placeholder="myuser"
                  class="mt-1 w-full rounded-xl bg-slate-950/60 border border-slate-700 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-600" />
              </div>
              <div>
                <label class="text-sm text-slate-300">Password (≥ 6 ตัว)</label>
                <input type="password" name="password" minlength="6" required placeholder="••••••••"
                  class="mt-1 w-full rounded-xl bg-slate-950/60 border border-slate-700 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-600" />
              </div>
              <button type="submit"
                class="w-full inline-flex justify-center items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-500 px-4 py-2 font-semibold">
                สมัครสมาชิก
              </button>
              <p class="text-xs text-slate-400">POST <code>api.php?action=register</code></p>
            </form>
          </div>
        </div>
      </div>

      <!-- Result -->
      <div class="col-span-2 md:col-span-1">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 shadow-lg backdrop-blur h-full">
          <div class="p-4 md:p-6">
            <div class="flex items-center justify-between mb-3">
              <h2 class="text-lg font-medium">ผลลัพธ์</h2>
              <button id="clearBtn" class="text-xs px-3 py-1 rounded-lg bg-slate-800 hover:bg-slate-700">ล้าง</button>
            </div>
            <pre id="output" class="text-sm whitespace-pre-wrap break-words p-3 rounded-xl bg-slate-950/50 border border-slate-800 min-h-[180px]">ยังไม่มีผลลัพธ์</pre>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    const $ = (s) => document.querySelector(s);
    const out = (v) => { $('#output').textContent = typeof v === 'string' ? v : JSON.stringify(v, null, 2); };
    $('#clearBtn').onclick = () => out('ยังไม่มีผลลัพธ์');

    const tabLogin = $('#tabLogin'), tabReg = $('#tabRegister');
    const loginForm = $('#loginForm'), regForm = $('#registerForm');

    function setTab(which){
      const active = 'bg-brand-600 hover:bg-brand-500';
      const inactive = 'bg-slate-800 hover:bg-slate-700';
      if (which === 'login') {
        tabLogin.classList.add(...active.split(' ')); tabLogin.classList.remove(...inactive.split(' '));
        tabReg.classList.add(...inactive.split(' ')); tabReg.classList.remove(...active.split(' '));
        loginForm.classList.remove('hidden'); regForm.classList.add('hidden');
      } else {
        tabReg.classList.add(...active.split(' ')); tabReg.classList.remove(...inactive.split(' '));
        tabLogin.classList.add(...inactive.split(' ')); tabLogin.classList.remove(...active.split(' '));
        regForm.classList.remove('hidden'); loginForm.classList.add('hidden');
      }
    }
    tabLogin.onclick = () => setTab('login');
    tabReg.onclick = () => setTab('register');

    async function post(url, data){
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      });
      const text = await res.text();
      try { return JSON.parse(text); } catch { return text; }
    }

    loginForm.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const f = e.currentTarget;
      out(await post('api.php?action=login', { email: f.email.value.trim(), password: f.password.value }));
    });

    regForm.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const f = e.currentTarget;
      out(await post('api.php?action=register', {
        email: f.email.value.trim(),
        username: f.username.value.trim() || undefined,
        password: f.password.value
      }));
    });
  </script>
</body>
</html>