import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';

void main() {
  runApp(const WeightsyMvpApp());
}

class WeightsyMvpApp extends StatelessWidget {
  const WeightsyMvpApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'weightsy MVP',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        brightness: Brightness.dark,
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF6EE7B7),
          brightness: Brightness.dark,
        ),
        scaffoldBackgroundColor: const Color(0xFF0B0B0D),
      ),
      home: const DashboardPage(),
    );
  }
}

class DashboardPage extends StatelessWidget {
  const DashboardPage({super.key});

  @override
  Widget build(BuildContext context) {
    const DemoUser user = DemoUser(
      contact: 'alertmorgan@gmail.com',
      dietStyle: 'Balanced',
      timezone: 'America/New_York',
    );

    final List<DemoEntry> entries = <DemoEntry>[
      const DemoEntry(day: 'D-9', weight: 330, bpSys: 146, bpDia: 94, bodyFat: 38.0),
      const DemoEntry(day: 'D-8', weight: 328, bpSys: 145, bpDia: 93, bodyFat: 37.8),
      const DemoEntry(day: 'D-7', weight: 327, bpSys: 144, bpDia: 92, bodyFat: 37.5),
      const DemoEntry(day: 'D-6', weight: 326, bpSys: 143, bpDia: 92, bodyFat: 37.3),
      const DemoEntry(day: 'D-5', weight: 324, bpSys: 142, bpDia: 91, bodyFat: 37.0),
      const DemoEntry(day: 'D-4', weight: 323, bpSys: 141, bpDia: 90, bodyFat: 36.8),
      const DemoEntry(day: 'D-3', weight: 322, bpSys: 141, bpDia: 90, bodyFat: 36.6),
      const DemoEntry(day: 'D-2', weight: 321, bpSys: 140, bpDia: 89, bodyFat: 36.4),
      const DemoEntry(day: 'D-1', weight: 319, bpSys: 139, bpDia: 88, bodyFat: 36.1),
      const DemoEntry(day: 'Today', weight: 318, bpSys: 138, bpDia: 88, bodyFat: 35.9),
    ];

    return Scaffold(
      appBar: AppBar(
        title: const Text('weightsy MVP'),
        backgroundColor: Colors.transparent,
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: <Widget>[
          _ProfileCard(user: user),
          const SizedBox(height: 12),
          _ChartCard(entries: entries),
          const SizedBox(height: 12),
          const _LegendCard(),
        ],
      ),
    );
  }
}

class _ProfileCard extends StatelessWidget {
  const _ProfileCard({required this.user});

  final DemoUser user;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFF131318),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFF2A2D36)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          const Text(
            'Demo Profile',
            style: TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
          ),
          const SizedBox(height: 8),
          Text('Contact: ${user.contact}'),
          Text('Diet style: ${user.dietStyle}'),
          Text('Timezone: ${user.timezone}'),
        ],
      ),
    );
  }
}

class _ChartCard extends StatelessWidget {
  const _ChartCard({required this.entries});

  final List<DemoEntry> entries;

  @override
  Widget build(BuildContext context) {
    final List<FlSpot> weightSpots = <FlSpot>[];
    final List<FlSpot> bpSysSpots = <FlSpot>[];
    final List<FlSpot> bpDiaSpots = <FlSpot>[];
    final List<FlSpot> bodyFatSpots = <FlSpot>[];

    for (int i = 0; i < entries.length; i++) {
      final DemoEntry e = entries[i];
      final double x = i.toDouble();
      weightSpots.add(FlSpot(x, e.weight.toDouble()));
      bpSysSpots.add(FlSpot(x, e.bpSys.toDouble()));
      bpDiaSpots.add(FlSpot(x, e.bpDia.toDouble()));
      bodyFatSpots.add(FlSpot(x, e.bodyFat));
    }

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFF131318),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFF2A2D36)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          const Text(
            'Combined Progress (0-400 scale)',
            style: TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
          ),
          const SizedBox(height: 14),
          SizedBox(
            height: 280,
            child: LineChart(
              LineChartData(
                minX: 0,
                maxX: (entries.length - 1).toDouble(),
                minY: 0,
                maxY: 400,
                gridData: FlGridData(
                  show: true,
                  horizontalInterval: 50,
                  getDrawingHorizontalLine: (double value) =>
                      const FlLine(color: Color(0x222A2D36), strokeWidth: 1),
                  drawVerticalLine: false,
                ),
                borderData: FlBorderData(
                  show: true,
                  border: const Border(
                    left: BorderSide(color: Color(0xFF2A2D36)),
                    bottom: BorderSide(color: Color(0xFF2A2D36)),
                    top: BorderSide.none,
                    right: BorderSide.none,
                  ),
                ),
                titlesData: FlTitlesData(
                  rightTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: false),
                  ),
                  topTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: false),
                  ),
                  leftTitles: AxisTitles(
                    sideTitles: SideTitles(
                      showTitles: true,
                      interval: 100,
                      reservedSize: 38,
                      getTitlesWidget: (double value, TitleMeta meta) => Text(
                        value.toInt().toString(),
                        style: const TextStyle(
                          color: Color(0xFF9FA3AE),
                          fontSize: 11,
                        ),
                      ),
                    ),
                  ),
                  bottomTitles: AxisTitles(
                    sideTitles: SideTitles(
                      showTitles: true,
                      reservedSize: 30,
                      interval: 1,
                      getTitlesWidget: (double value, TitleMeta meta) {
                        final int idx = value.round();
                        if (idx < 0 || idx >= entries.length) {
                          return const SizedBox.shrink();
                        }
                        return Padding(
                          padding: const EdgeInsets.only(top: 8),
                          child: Text(
                            entries[idx].day,
                            style: const TextStyle(
                              color: Color(0xFF9FA3AE),
                              fontSize: 10,
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                ),
                lineBarsData: <LineChartBarData>[
                  _line(weightSpots, const Color(0xFF6EE7B7), 2.8),
                  _line(bpSysSpots, const Color(0xFF60A5FA), 2.2),
                  _line(bpDiaSpots, const Color(0xFFF59E0B), 2.2),
                  _line(bodyFatSpots, const Color(0xFFF472B6), 2.2),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  LineChartBarData _line(List<FlSpot> points, Color color, double width) {
    return LineChartBarData(
      spots: points,
      isCurved: true,
      color: color,
      barWidth: width,
      dotData: const FlDotData(show: false),
      belowBarData: BarAreaData(show: false),
    );
  }
}

class _LegendCard extends StatelessWidget {
  const _LegendCard();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFF131318),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFF2A2D36)),
      ),
      child: Wrap(
        spacing: 14,
        runSpacing: 10,
        children: const <Widget>[
          _Legend(label: 'Weight', color: Color(0xFF6EE7B7)),
          _Legend(label: 'BP Sys', color: Color(0xFF60A5FA)),
          _Legend(label: 'BP Dia', color: Color(0xFFF59E0B)),
          _Legend(label: 'Body Fat %', color: Color(0xFFF472B6)),
        ],
      ),
    );
  }
}

class _Legend extends StatelessWidget {
  const _Legend({required this.label, required this.color});

  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: <Widget>[
        Container(
          width: 10,
          height: 10,
          decoration: BoxDecoration(color: color, shape: BoxShape.circle),
        ),
        const SizedBox(width: 6),
        Text(label),
      ],
    );
  }
}

class DemoUser {
  const DemoUser({
    required this.contact,
    required this.dietStyle,
    required this.timezone,
  });

  final String contact;
  final String dietStyle;
  final String timezone;
}

class DemoEntry {
  const DemoEntry({
    required this.day,
    required this.weight,
    required this.bpSys,
    required this.bpDia,
    required this.bodyFat,
  });

  final String day;
  final int weight;
  final int bpSys;
  final int bpDia;
  final double bodyFat;
}
