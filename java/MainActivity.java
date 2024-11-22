package com.example.helloworld;

import android.app.Activity;
import android.os.Bundle;
import android.widget.TextView;
import android.graphics.Color;

public class MainActivity extends Activity {
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        TextView textView = new TextView(this);
        textView.setText("Hello World!");
        textView.setTextSize(24); // Make text bigger
        textView.setTextColor(Color.BLACK); // Set text color
        textView.setPadding(20, 20, 20, 20); // Add some padding
        setContentView(textView);
    }
}
