import 'package:flatcare_mobile/features/vehicles/data/society_vehicles.dart';
import 'package:flatcare_mobile/features/vehicles/screens/society_vehicles_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

VehicleOwner _owner(int id) => VehicleOwner(
      userId: id,
      name: id == 1 ? 'Pravin Panchal' : 'Meera Shah',
      phone: '9876543210',
      flatNumber: id == 1 ? 'B 101' : 'C 201',
      blockName: id == 1 ? 'B' : 'C',
      residentType: id == 1 ? 'owner' : 'tenant',
      vehicles: [ResidentVehicle(id: id, type: 'Car', registrationNumber: id == 1 ? 'GJ 18 BG 4449' : 'GJ 01 AB 1234')],
    );

/// null = all blocks.
SocietyVehicles _block(int? id) => SocietyVehicles(
      blocks: const [VehicleBlock(id: 1, name: 'B'), VehicleBlock(id: 2, name: 'C')],
      blockId: id,
      counts: [VehicleTypeCount(type: 'Car', count: id == null ? 3 : (id == 1 ? 2 : 1))],
      residents: [if (id != 2) _owner(1), if (id != 1) _owner(2)],
    );

void main() {
  testWidgets('shows every block by default and narrows to the tapped block', (tester) async {
    tester.view.physicalSize = const Size(1080, 2400);
    tester.view.devicePixelRatio = 2;
    addTearDown(tester.view.reset);

    await tester.pumpWidget(ProviderScope(
      overrides: [
        societyVehiclesProvider.overrideWith((ref) async => _block(ref.watch(vehicleBlockProvider))),
      ],
      child: const MaterialApp(home: SocietyVehiclesScreen()),
    ));
    await tester.pumpAndSettle();

    expect(find.text('All'), findsOneWidget);
    expect(find.text('Pravin Panchal'), findsOneWidget);
    expect(find.text('Meera Shah'), findsOneWidget);
    expect(find.text('3'), findsOneWidget); // Car count, all blocks

    await tester.tap(find.text('B'));
    await tester.pumpAndSettle();

    expect(find.text('Pravin Panchal'), findsOneWidget);
    expect(find.text('Meera Shah'), findsNothing);
    expect(find.text('Owner'), findsOneWidget);
    expect(find.text('GJ 18 BG 4449'), findsOneWidget);
    expect(find.text('2'), findsOneWidget); // Car count
    expect(find.text('My Vehicles'), findsOneWidget);
    expect(find.byTooltip('WhatsApp Pravin Panchal'), findsOneWidget);

    await tester.tap(find.text('C'));
    await tester.pumpAndSettle();

    expect(find.text('Meera Shah'), findsOneWidget);
    expect(find.text('Rent'), findsOneWidget);
    expect(find.text('Pravin Panchal'), findsNothing);

    await tester.tap(find.text('All'));
    await tester.pumpAndSettle();

    expect(find.text('Pravin Panchal'), findsOneWidget);
    expect(find.text('Meera Shah'), findsOneWidget);
  });
}
