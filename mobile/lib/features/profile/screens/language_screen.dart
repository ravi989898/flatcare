import 'package:flutter/material.dart';

/// English is the only language the app ships with today — this screen
/// exists so the mockup's Profile menu entry has somewhere to go, ready to
/// grow into a real picker once more locales are translated.
class LanguageScreen extends StatelessWidget {
  const LanguageScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Language')),
      body: ListView(
        children: const [
          ListTile(title: Text('English'), trailing: Icon(Icons.check, color: Colors.green)),
        ],
      ),
    );
  }
}
