import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/theme/app_theme.dart';
import '../data/guard_vehicle.dart';

/// Full detail for one vehicle from the gate register — who it belongs to,
/// which flat, and a tap-to-call for the owner, so a guard can actually
/// follow up on a vehicle at the gate instead of just seeing a name.
/// Opened from GatekeeperVehicleListScreen with the already-fetched
/// GuardVehicle passed via `extra` (same pattern as GatePassScreen), so
/// this needs no API call of its own.
class GatekeeperVehicleDetailScreen extends StatelessWidget {
  const GatekeeperVehicleDetailScreen({super.key, required this.vehicle});

  final GuardVehicle vehicle;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF2F3F7),
      appBar: AppBar(
        title: const Text('Vehicle Details'),
        foregroundColor: Colors.white,
        flexibleSpace: const DecoratedBox(decoration: BoxDecoration(gradient: AppTheme.brandGradient)),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: AppTheme.brandGradient,
              borderRadius: BorderRadius.circular(18),
            ),
            child: Column(
              children: [
                const Icon(Icons.directions_car_rounded, color: Colors.white, size: 34),
                const SizedBox(height: 8),
                Text(
                  vehicle.registrationNumber,
                  style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w800, letterSpacing: .5),
                ),
                if (vehicle.model != null || vehicle.color != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Text(
                      [vehicle.color, vehicle.model].whereType<String>().join(' · '),
                      style: TextStyle(color: Colors.white.withValues(alpha: 0.9), fontSize: 13),
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          _DetailCard(
            title: 'Owner',
            rows: [
              _DetailRow(icon: Icons.person_outline, color: const Color(0xFF8E5FE0), label: 'Name', value: vehicle.ownerName ?? 'Not on file'),
              _DetailRow(
                icon: Icons.home_outlined,
                color: const Color(0xFF1783C0),
                label: 'Flat',
                value: vehicle.flatLabel ?? 'Not on file',
              ),
              _DetailRow(
                icon: Icons.phone_outlined,
                color: const Color(0xFF2AB98A),
                label: 'Mobile Number',
                value: vehicle.ownerPhone ?? 'Not on file',
                onTap: vehicle.ownerPhone != null ? () => launchUrl(Uri.parse('tel:${vehicle.ownerPhone}')) : null,
              ),
            ],
          ),
          const SizedBox(height: 16),
          _DetailCard(
            title: 'Vehicle',
            rows: [
              _DetailRow(icon: Icons.category_outlined, color: const Color(0xFFF5A623), label: 'Type', value: vehicle.vehicleType),
              if (vehicle.year != null) _DetailRow(icon: Icons.event_outlined, color: const Color(0xFFF5A623), label: 'Year', value: vehicle.year!),
              if (vehicle.parkingSlot != null)
                _DetailRow(icon: Icons.local_parking_outlined, color: const Color(0xFFE0245E), label: 'Parking Slot', value: vehicle.parkingSlot!),
              _DetailRow(icon: Icons.verified_outlined, color: const Color(0xFF2AB930), label: 'Status', value: vehicle.status),
            ],
          ),
        ],
      ),
    );
  }
}

class _DetailCard extends StatelessWidget {
  const _DetailCard({required this.title, required this.rows});

  final String title;
  final List<_DetailRow> rows;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 4),
            child: Text(
              title.toUpperCase(),
              style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Colors.grey.shade500, letterSpacing: .4),
            ),
          ),
          for (final row in rows) row,
        ],
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  const _DetailRow({required this.icon, required this.color, required this.label, required this.value, this.onTap});

  final IconData icon;
  final Color color;
  final String label;
  final String value;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      onTap: onTap,
      leading: CircleAvatar(backgroundColor: color.withValues(alpha: 0.14), child: Icon(icon, color: color, size: 20)),
      title: Text(label, style: TextStyle(fontSize: 11.5, color: Colors.grey.shade600)),
      subtitle: Text(value, style: const TextStyle(fontSize: 14.5, fontWeight: FontWeight.w700)),
      trailing: onTap != null ? Icon(Icons.call, color: color, size: 20) : null,
    );
  }
}
