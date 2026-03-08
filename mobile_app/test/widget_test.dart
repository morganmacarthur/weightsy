import 'package:flutter_test/flutter_test.dart';
import 'package:mobile_app/main.dart';

void main() {
  testWidgets('weightsy MVP renders dashboard title', (WidgetTester tester) async {
    await tester.pumpWidget(const WeightsyMvpApp());
    expect(find.text('weightsy MVP'), findsOneWidget);
  });
}
