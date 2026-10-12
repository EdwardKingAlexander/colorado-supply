<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SmsRecipient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Twilio\Security\RequestValidator;

/**
 * Replies to our Twilio number (ai/modules/will-call-notifications, phase 6).
 * STOP-type words opt the number out of every text right away; HELP gets
 * help; START-type words opt back in. Requests must carry a valid Twilio
 * signature.
 */
class TwilioSmsWebhookController extends Controller
{
    private const STOP_WORDS = ['STOP', 'STOPALL', 'UNSUBSCRIBE', 'CANCEL', 'END', 'QUIT', 'OPTOUT', 'REVOKE'];

    private const START_WORDS = ['START', 'UNSTOP', 'YES'];

    private const HELP_WORDS = ['HELP', 'INFO'];

    public function handle(Request $request): Response
    {
        $token = config('twilio-notification-channel.auth_token');
        $signature = (string) $request->header('X-Twilio-Signature');

        // Twilio signs the parameters exactly as sent; Laravel's input is
        // already trimmed/nulled by middleware, so read the raw body.
        parse_str($request->getContent(), $params);

        if (blank($token) || ! (new RequestValidator($token))->validate($signature, $request->fullUrl(), $params)) {
            abort(403);
        }

        $phone = SmsRecipient::normalize($params['From'] ?? null);
        $firstWord = strtok(trim((string) ($params['Body'] ?? '')), " \n\t") ?: '';
        $word = strtoupper((string) preg_replace('/[^A-Za-z]/', '', $firstWord));

        if ($phone && in_array($word, self::STOP_WORDS, true)) {
            SmsRecipient::query()->where('phone_e164', $phone)->whereNull('opted_out_at')->update(['opted_out_at' => now()]);

            // Twilio sends the carrier-required STOP confirmation itself.
            return $this->twiml();
        }

        if ($phone && in_array($word, self::START_WORDS, true)) {
            SmsRecipient::query()->where('phone_e164', $phone)->update(['opted_out_at' => null]);

            return $this->twiml();
        }

        if (in_array($word, self::HELP_WORDS, true)) {
            return $this->twiml(config('sms.help_reply'));
        }

        return $this->twiml();
    }

    private function twiml(?string $message = null): Response
    {
        $body = $message === null
            ? '<Response/>'
            : '<Response><Message>'.htmlspecialchars($message, ENT_XML1).'</Message></Response>';

        return response('<?xml version="1.0" encoding="UTF-8"?>'.$body, 200, ['Content-Type' => 'text/xml']);
    }
}
