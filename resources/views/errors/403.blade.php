<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Accès refusé — MediGuide</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',-apple-system,system-ui,sans-serif;background:#F7F9FC;color:#33415A;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:28px}
.err-card{background:#fff;border:1px solid #EEF2F8;border-radius:26px;box-shadow:0 10px 28px rgba(2,132,199,.10),0 3px 8px rgba(16,27,45,.05);padding:48px;max-width:440px;text-align:center}
.mk{width:64px;height:64px;margin:0 auto 20px;border-radius:50%;background:#FEE2E2;display:flex;align-items:center;justify-content:center}
.mk svg{width:30px;height:30px;stroke:#DC2626;stroke-width:1.8;fill:none}
h1{font-size:1.4rem;font-weight:800;color:#101B2D;margin-bottom:10px}
p{color:#64748B;font-size:.95rem;line-height:1.6;margin-bottom:24px}
a{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#0EA5E9 0%,#0284C7 52%,#0369A1 100%);color:#fff;font-weight:700;font-size:.92rem;padding:13px 25px;border-radius:999px;text-decoration:none;box-shadow:0 8px 20px rgba(2,132,199,.32)}
</style>
</head>
<body>
<div class="err-card">
    <div class="mk"><svg viewBox="0 0 24 24"><path d="M12 3l8 3v6c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V6z"/></svg></div>
    <h1>Accès refusé</h1>
    <p>{{ $exception->getMessage() ?: "Vous n'avez pas les droits nécessaires pour accéder à cette page." }}</p>
    <a href="{{ route('dashboard') }}">Retour à mon espace</a>
</div>
</body>
</html>
