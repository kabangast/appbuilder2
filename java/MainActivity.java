package com.example.helloworld;

import android.app.Activity;
import android.os.Bundle;
import android.webkit.WebView;
import android.webkit.WebViewClient;

public class MainActivity extends Activity {
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        WebView webView = new WebView(this);
        webView.setWebViewClient(new WebViewClient()); // This line prevents opening in external browser
        webView.getSettings().setJavaScriptEnabled(true);
        webView.loadUrl("https://example.com");
        setContentView(webView);
    }
}
