import 'package:flutter/material.dart';

import '../theme/app_colors.dart';

/// A vehicle type the app offers, with the icon and accent it's shown with
/// on every vehicle card (resident Vehicles screen and the gate register).
class VehicleTypeStyle {
  const VehicleTypeStyle(this.label, this.icon, this.color);

  final String label;
  final IconData icon;
  final Color color;
}

const vehicleTypeOther = 'Other';

const vehicleTypes = [
  VehicleTypeStyle('Car', Icons.directions_car_rounded, AppColors.accentSky),
  VehicleTypeStyle('Bike', Icons.two_wheeler_rounded, AppColors.accentRose),
  VehicleTypeStyle('Scooter', Icons.moped_rounded, AppColors.accentViolet),
  VehicleTypeStyle('Auto', Icons.electric_rickshaw_rounded, AppColors.accentAmber),
  VehicleTypeStyle('Van', Icons.airport_shuttle_rounded, AppColors.accentTeal),
  VehicleTypeStyle('Truck', Icons.local_shipping_rounded, AppColors.accentIndigo),
  VehicleTypeStyle(vehicleTypeOther, Icons.commute_rounded, AppColors.accentSlate),
];

/// Values saved before the dropdown existed (the old enum) still get a
/// fitting icon.
const _legacy = {
  'cycle': VehicleTypeStyle('Cycle', Icons.pedal_bike_rounded, AppColors.accentTeal),
  'commercial': VehicleTypeStyle('Commercial', Icons.local_shipping_rounded, AppColors.accentIndigo),
};

/// The listed type matching [value] (case-insensitive), or null for a
/// free-text "Other" value.
VehicleTypeStyle? knownVehicleType(String value) {
  final key = value.trim().toLowerCase();
  return vehicleTypes.where((t) => t.label.toLowerCase() == key).firstOrNull;
}

/// Style for any stored vehicle_type — free-text values fall back to Other's
/// icon but keep their own label.
VehicleTypeStyle vehicleTypeStyle(String value) {
  final known = knownVehicleType(value) ?? _legacy[value.trim().toLowerCase()];
  if (known != null) return known;

  final other = vehicleTypes.last;
  final label = value.trim().isEmpty ? other.label : value.trim()[0].toUpperCase() + value.trim().substring(1);
  return VehicleTypeStyle(label, other.icon, other.color);
}
