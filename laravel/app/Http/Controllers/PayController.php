<?php

namespace App\Http\Controllers;

use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Psr\Http\Message\ResponseInterface;

class PayController extends Controller
{
    /**
     * リダイレクト前処理
     */
    public function redirect(Request $request): RedirectResponse
    {
        // 支払い作成
        $response = $this->createCharge($request->cardToken, 1000);
        if ($response->getStatusCode() !== 200) {
            return redirect('payment')->with('errorMessage', '支払いの作成に失敗しました。');
        }
        $body = json_decode($response->getBody()->getContents(), true);

        // 支払いIDをセッションに保存（リダイレクトに取得するため）
        $request->session()->put('pay_id', $body['id']);

        // PAY.JP側へリダイレクト
        $query = http_build_query([
            'publickey' => config('pay.public_key'),
            'back_url' => JWT::encode(['url' => url('/callback')], config('pay.secret_key'), 'HS256'),
        ]);

        return redirect("https://api.pay.jp/v1/tds/{$body['id']}/start?{$query}");
    }

    public function callback(Request $request)
    {
        // 支払いID取得（取得できなければ不正アクセスとしてエラー）
        $payId = $request->session()->get('pay_id');
        if (empty($payId)) {
            abort(404);
        }

        // 支払い情報取得
        $response = $this->fetchCharge($payId);
        if ($response->getStatusCode() !== 200) {
            // エラー処理
            return redirect('payment')->with('errorMessage', '支払いの取得に失敗しました。');
        }
        $body = json_decode($response->getBody()->getContents(), true);

        // 完全認証の場合はアテンプト（3Dセキュア未登録のカードで決済）をエラーとして扱う
        $threeDSecureStatus = $body['three_d_secure_status'];
        if ($threeDSecureStatus === 'attempted') {
            return redirect('payment')->with('errorMessage', 'ご利用のカードは使用できません。');
        }

        // 支払い完了
        $response = $this->finishCharge($payId);
        if ($response->getStatusCode() !== 200) {
            return redirect('payment')->with('errorMessage', 'カード決済に失敗しました。');
        }

        return redirect('complete');
    }

    /**
     * 支払いを作成する
     */
    private function createCharge(string $token, int $amount): ResponseInterface
    {
        $client = new Client([
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'auth' => [config('pay.secret_key'), ''],
        ]);
        try {
            return $client->request('POST', 'https://api.pay.jp/v1/charges', ['form_params' => [
                'amount' => $amount,
                'currency' => 'jpy',
                'card' => $token,
                'three_d_secure' => 'true',
                'capture' => 'false',   // 'false' の場合は支払いを確定せずに、カードの認証と支払い額のみ確保
            ]]);
        } catch (ClientException $e) {
            return $e->getResponse();
        }
    }

    /**
     * 支払い情報を取得する
     */
    private function fetchCharge(string $payId): ResponseInterface
    {
        $client = new Client(['auth' => [config('pay.secret_key'), '']]);
        try {
            return $client->request('GET', "https://api.pay.jp/v1/charges/{$payId}");
        } catch (ClientException $e) {
            return $e->getResponse();
        }
    }

    /**
     * 支払いを完了させる
     */
    private function finishCharge(string $payId): ResponseInterface
    {
        $client = new Client([
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'auth' => [config('pay.secret_key'), ''],
        ]);
        try {
            return $client->request('POST', "https://api.pay.jp/v1/charges/{$payId}/tds_finish");
        } catch (ClientException $e) {
            return $e->getResponse();
        }
    }
}