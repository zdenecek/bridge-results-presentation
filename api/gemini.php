<?php

const SLIP_PROMPT = <<<'PROMPT'
The photo shows a handwritten bridge travelling score slip, possibly rotated.
Each row is one board with columns: deal number, dealer & vulnerability,
contract & declarer, lead, tricks, score NS, score EW.

Transcribe every played board as one line, ordered by board number:
<board> <level><suit><doubling><declarer> <result> <score>

- suit: C, D, H, S or NT. Suits are drawn by hand: clubs may look like "+",
  no trumps like a circle or "O".
- doubling: x for doubled, xx for redoubled, nothing otherwise.
- declarer: N, E, S or W.
- result: "=" when made exactly, "+1", "+2"… for overtricks, "-1", "-2"… for undertricks.
- score: the number written in the NS or EW score column, without a sign.
- A passed-out board is "<board> pass".

Examples: "2 6DW +1 940", "13 3NTS = 600", "14 4SxW -2 300".
If pair names are written on the slip, start with "# NS: <names> / EW: <names>".
Output only these lines, nothing else. Skip boards that were not played.
PROMPT;

function geminiRequestBody(string $image, string $mimeType): array
{
    return [
        'contents' => [[
            'parts' => [
                ['text' => SLIP_PROMPT],
                ['inline_data' => ['mime_type' => $mimeType, 'data' => $image]],
            ],
        ]],
        'generationConfig' => ['temperature' => 0],
    ];
}

function geminiResponseText(array $response): string
{
    $parts = $response['candidates'][0]['content']['parts'] ?? [];
    $text = implode('', array_map(
        fn($part) => empty($part['thought']) ? ($part['text'] ?? '') : '',
        $parts
    ));
    return trim(preg_replace('/^```\w*\s*|\s*```$/', '', trim($text)));
}

function callGemini(string $model, string $key, array $body): array
{
    $curl = curl_init("https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent");
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', "x-goog-api-key: $key"],
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 75,
    ]);
    $response = curl_exec($curl);
    if ($response === false) throw new RuntimeException('Gemini nedostupné: ' . curl_error($curl));
    return [curl_getinfo($curl, CURLINFO_RESPONSE_CODE), json_decode($response, true) ?? []];
}

/**
 * Sends a base64 photo of a slip to Gemini and returns the transcribed lines and the model used.
 * When a model is overloaded, out of quota or retired, the next one in `models` is tried.
 */
function transcribeSlip(string $image, string $mimeType, array $gemini): array
{
    $models = $gemini['models'] ?? ['gemini-flash-latest', 'gemini-3.5-flash', 'gemini-3.5-flash-lite'];
    $body = geminiRequestBody($image, $mimeType);

    foreach ($models as $i => $model) {
        [$status, $response] = callGemini($model, $gemini['key'], $body);
        $tryNext = in_array($status, [404, 429, 503], true);
        if ($tryNext && $i < count($models) - 1) continue;
        if ($status !== 200) throw new RuntimeException('Gemini: ' . ($response['error']['message'] ?? "HTTP $status"));

        $text = geminiResponseText($response);
        if ($text === '') throw new RuntimeException('Gemini nevrátilo žádný text');
        return ['text' => $text, 'model' => $model];
    }
    throw new RuntimeException('Gemini: žádný model');
}
