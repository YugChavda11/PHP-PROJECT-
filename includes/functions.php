<?php
// =============================================================
// includes/functions.php  — Shared helpers
// =============================================================

// ---- CSRF -------------------------------------------------------
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES) . '">';
}

function verifyCsrf(): void
{
    if (
        empty($_POST['csrf_token']) ||
        !hash_equals(csrfToken(), $_POST['csrf_token'])
    ) {
        http_response_code(403);
        die('<div style="font-family:sans-serif;padding:2rem;color:#c00;">
               <strong>CSRF validation failed.</strong> Please go back and try again.
             </div>');
    }
}

// ---- Output escaping -------------------------------------------
function e(string $val): string
{
    return htmlspecialchars($val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ---- Currency formatting ---------------------------------------
function formatMoney(float $amount): string
{
    return CURRENCY_SYMBOL . number_format($amount, 2, '.', ',');
}

// ---- Date helpers ----------------------------------------------
function formatDate(string $date): string
{
    return date('d M Y', strtotime($date));
}

function currentMonthStart(): string { return date('Y-m-01'); }
function currentMonthEnd(): string   { return date('Y-m-t'); }
function lastMonthStart(): string    { return date('Y-m-01', strtotime('first day of last month')); }
function lastMonthEnd(): string      { return date('Y-m-t',  strtotime('last day of last month'));  }
function ytdStart(): string          { return date('Y-01-01'); }
function today(): string             { return date('Y-m-d'); }

// ---- Flash messages --------------------------------------------
function setFlash(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function renderFlash(): void
{
    $f = getFlash();
    if (!$f) return;
    $type = in_array($f['type'], ['success','danger','warning','info']) ? $f['type'] : 'info';
    echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
       . e($f['msg'])
       . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
       . '</div>';
}

// ---- Pagination ------------------------------------------------
function paginationLinks(int $currentPage, int $totalPages, string $baseUrl): string
{
    if ($totalPages <= 1) return '';
    $html = '<nav aria-label="Page navigation"><ul class="pagination pagination-sm justify-content-center flex-wrap mb-0">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i === $currentPage ? ' active' : '';
        $html .= '<li class="page-item' . $active . '">'
               . '<a class="page-link" href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a>'
               . '</li>';
    }
    $html .= '</ul></nav>';
    return $html;
}

// ---- Build query string (preserve existing params) -------------
function buildUrl(string $base, array $params): string
{
    return $base . '?' . http_build_query($params);
}

// ---- Sanitize string input ------------------------------------
function clean(?string $val): string
{
    return trim(strip_tags((string)$val));
}

// ---- Validate positive decimal --------------------------------
function isPositiveDecimal(mixed $val): bool
{
    return is_numeric($val) && (float)$val > 0;
}
