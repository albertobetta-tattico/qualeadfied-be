<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LeadChangeController extends Controller
{
    // Placeholders that must be replaced before sending the link to the user
    private const PLACEHOLDERS = ['USER_EMAIL', 'USER_PHONE', 'EMAIL_UTENTE', 'TEL_UTENTE'];

    public function __invoke(Request $request): Response
    {
        $email = trim((string) $request->input('email'));
        $tel = trim((string) $request->input('tel'));
        $categoryParam = $request->input('category_destination');

        // 1. Category must be present and valid
        if (! $categoryParam || ! ctype_digit((string) $categoryParam)) {
            return $this->errorPage('Link non valido: categoria di destinazione mancante o non corretta.');
        }

        $categoryId = (int) $categoryParam;

        if (! \App\Models\Category::where('id', $categoryId)->exists()) {
            return $this->errorPage('Link non valido: la categoria di destinazione non esiste.');
        }

        // 2. Email/phone placeholders must be replaced
        if (in_array($email, self::PLACEHOLDERS, true) || in_array($tel, self::PLACEHOLDERS, true)) {
            return $this->errorPage('Link non valido: i parametri non sono stati personalizzati correttamente.');
        }

        // Treat placeholder-like values as empty
        if ($email === '' || in_array($email, self::PLACEHOLDERS, true)) {
            $email = null;
        }
        if ($tel === '' || in_array($tel, self::PLACEHOLDERS, true)) {
            $tel = null;
        }

        // 3. At least one identifier required
        if (! $email && ! $tel) {
            return $this->errorPage('Link non valido: e-mail o numero di telefono mancanti.');
        }

        // 4. Validate email format if provided
        if ($email !== null && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->errorPage('Link non valido: formato e-mail non corretto.');
        }

        // 5. Find lead by email or phone
        $query = Lead::query();

        $query->where(function ($q) use ($email, $tel) {
            if ($email) {
                $q->where('email', $email);
            }
            if ($tel) {
                if ($email) {
                    $q->orWhere('phone', $tel);
                } else {
                    $q->where('phone', $tel);
                }
            }
        });

        $lead = $query->first();

        // 6. Lead must exist
        if (! $lead) {
            return $this->errorPage('Non &egrave; stato possibile trovare un contatto corrispondente. Verifica i dati e riprova.');
        }

        // 7. Attach category (idempotent)
        $lead->categories()->syncWithoutDetaching([$categoryId]);

        return $this->thankYouPage();
    }

    private function thankYouPage(): Response
    {
        $body = '<h1>Grazie per aver manifestato interesse per la nostra promozione, abbiamo raccolto la tua adesione</h1>'
            . '<p>Sarai contattato al pi&ugrave; presto dai nostri partner commerciali per ulteriori approfondimenti, senza impegno da parte tua</p>';

        return $this->renderPage('Grazie', $body, '#2d3748', 200);
    }

    private function errorPage(string $message): Response
    {
        $body = '<h1>Si &egrave; verificato un errore</h1>'
            . '<p>' . $message . '</p>';

        return $this->renderPage('Errore', $body, '#c53030', 400);
    }

    private function renderPage(string $title, string $body, string $titleColor, int $status): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            min-height: 100vh;
            padding-top: 80px;
            background: #fff;
            color: #333;
        }
        .container {
            text-align: center;
            max-width: 600px;
            padding: 40px 24px;
        }
        h1 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: {$titleColor};
            line-height: 1.4;
        }
        p {
            font-size: 1.1rem;
            color: #4a5568;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">{$body}</div>
</body>
</html>
HTML;

        return response($html, $status)->header('Content-Type', 'text/html');
    }
}
