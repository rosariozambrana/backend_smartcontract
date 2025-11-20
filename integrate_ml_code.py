#!/usr/bin/env python3
"""
Script para integrar código ML en detalle_inmuebles.dart
Inserta fragmentos de código en ubicaciones específicas
"""

def integrate_ml_code():
    file_path = r"D:\Semestre 2-2025\Sofware II\proygrupalsw\proygrupalsw\rentals\lib\presentacion\screens\home_propietario\inmuebles\detalle_inmuebles.dart"

    # Leer archivo
    with open(file_path, 'r', encoding='utf-8') as f:
        lines = f.readlines()

    # 1. Inicialización en initState (buscar línea con _isOcupado = widget.inmueble!.isOcupado)
    init_code = '''
      // 🆕 Inicializar campos ML si existen
      _metrosCuadradosController.text = widget.inmueble!.metrosCuadrados?.toString() ?? '';
      _numBanosController.text = widget.inmueble!.numBanos?.toString() ?? '';
      _selectedLatitude = widget.inmueble!.latitude;
      _selectedLongitude = widget.inmueble!.longitude;
      _direccionSeleccionada = widget.inmueble!.direccion;

      // 🆕 Inicializar parking/piscina desde accesorios
      if (widget.inmueble!.accesorios != null) {
        _tieneParking = widget.inmueble!.accesorios!.any((acc) =>
          acc['nombre']?.toString().toLowerCase().contains('parking') ?? false
        );
        _tienePiscina = widget.inmueble!.accesorios!.any((acc) =>
          acc['nombre']?.toString().toLowerCase().contains('piscina') ?? false
        );
      }
'''

    # Buscar línea "_tipoInmuebleId = widget.inmueble!.tipoInmuebleId"
    for i, line in enumerate(lines):
        if '_tipoInmuebleId = widget.inmueble!.tipoInmuebleId' in line:
            # Insertar después de esta línea
            lines.insert(i + 1, init_code)
            break

    # 2. Dispose controllers (buscar línea con "_precioController.dispose()")
    dispose_code = '''    _metrosCuadradosController.dispose();
    _numBanosController.dispose();
'''

    for i, line in enumerate(lines):
        if '_precioController.dispose()' in line:
            lines.insert(i + 1, dispose_code)
            break

    # Escribir archivo modificado
    with open(file_path, 'w', encoding='utf-8') as f:
        f.writelines(lines)

    print("OK: Código ML integrado exitosamente")
    print(f"📝 Total líneas: {len(lines)}")

if __name__ == '__main__':
    integrate_ml_code()
