<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LeadChangeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $request->validate([
            'email' => ['nullable', 'string'],
            'tel' => ['nullable', 'string'],
            'category_destination' => ['required', 'exists:categories,id'],
        ]);

        $email = $request->input('email');
        $tel = $request->input('tel');
        $categoryId = (int) $request->input('category_destination');

        if (! $email && ! $tel) {
            return $this->thankYouPage();
        }

        // Find lead by email or phone
        $query = Lead::query();

        if ($email) {
            $query->where('email', $email);
        }

        if ($tel) {
            $query->orWhere('phone', $tel);
        }

        $lead = $query->first();

        if ($lead) {
            // Attach the new category if not already attached
            $lead->categories()->syncWithoutDetaching([$categoryId]);
        }

        return $this->thankYouPage();
    }

    private function thankYouPage(): Response
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grazie</title>
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
            color: #2d3748;
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
    <div class="container">
        <h1>Grazie per aver manifestato interesse per la nostra promozione, abbiamo raccolto la tua adesione</h1>
        <p>Sarai contattato al pi&ugrave; presto dai nostri partner commerciali per ulteriori approfondimenti, senza impegno da parte tua</p>
    </div>
</body>
</html>
HTML;

        return response($html, 200)->header('Content-Type', 'text/html');
    }
}
