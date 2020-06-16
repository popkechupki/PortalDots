<?php

namespace App\Http\Controllers\Circles\Users;

use Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Eloquents\Circle;
use QrCode;
use BaconQrCode\Exception\RuntimeException;

class IndexAction extends Controller
{
    public function __invoke(Circle $circle, Request $request)
    {
        $this->authorize('circle.update', $circle);

        if (!Auth::user()->isLeaderInCircle($circle)) {
            abort(403);
        }

        $circle->load('users');

        // 開発環境で http: が表示されないことがあるが、開発環境以外では
        // 正常に表示されるので問題がない
        $invitation_url = route('circles.users.invite', [
            'circle' => $circle,
            'token' => $circle->invitation_token
        ]);

        $invitation_url_for_blade = str_replace('"', '', \json_encode($invitation_url, JSON_UNESCAPED_SLASHES));

        $qrcode_html = '';

        try {
            $qrcode_html = QrCode::margin(0)
                ->size(180)
                ->generate($invitation_url_for_blade);
        } catch (RuntimeException $e) {
            // libxml 拡張機能がサーバーにインストールされていない場合、
            // QRコードは表示しない
            $qrcode_html = '';
        }

        return view('v2.circles.users.index')
            ->with('circle', $circle)
            ->with('invitation_url', $invitation_url_for_blade)
            ->with('qrcode_html', $qrcode_html)
            ->with('share_json', \json_encode([
                'url' => $invitation_url,
            ], JSON_UNESCAPED_SLASHES));
    }
}
