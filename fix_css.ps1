$f = 'c:\xampp\htdocs\pro\assets\css\public.css'
$b = [IO.File]::ReadAllBytes($f)
$s = [Text.Encoding]::UTF8.GetString($b)

$old = ".footer-social a {`r`n  font-size: 1rem;`r`n`r`n  color: rgba(255,255,255,0.3);`r`n  transition: color 0.25s, transform 0.3s var(--ease-expo);`r`n  display: flex;`r`n}`r`n`r`n.footer-social a:hover { color: rgba(255,255,255,0.9); transform: translateY(-3px); }"

$new = ".footer-social a {`r`n  font-size: 1.35rem;`r`n  color: rgba(255,255,255,0.55);`r`n  transition: color 0.25s, transform 0.3s var(--ease-expo);`r`n  display: flex;`r`n}`r`n.footer-social a[aria-label=""Instagram""] { color: #e1306c; }`r`n.footer-social a[aria-label=""LinkedIn""]  { color: #0a66c2; }`r`n.footer-social a[aria-label=""Behance""]   { color: #1769ff; }`r`n.footer-social a[aria-label=""Twitter""]   { color: #1da1f2; }`r`n.footer-social a[aria-label=""Telegram""]  { color: #2aabee; }`r`n.footer-social a[aria-label=""Facebook""]  { color: #1877f2; }`r`n.footer-social a[aria-label=""TikTok""]    { color: #ee1d52; }`r`n.footer-social a[aria-label=""YouTube""]   { color: #ff0000; }`r`n.footer-social a:hover { color: #fff; transform: translateY(-3px); }"

$s2 = $s.Replace($old, $new)
if ($s2 -eq $s) { Write-Host "NOT FOUND" } else { [IO.File]::WriteAllBytes($f, [Text.Encoding]::UTF8.GetBytes($s2)); Write-Host "Done" }
