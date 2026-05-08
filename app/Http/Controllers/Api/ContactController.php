<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:255',
            'category' => 'required|string|max:100',
            'body'     => 'required|string|max:3000',
        ]);

        Mail::to('desk@call-ops.jp')->send(
            new ContactMail($data['name'], $data['email'], $data['category'], $data['body'])
        );

        return response()->json(['message' => '送信しました。']);
    }
}
