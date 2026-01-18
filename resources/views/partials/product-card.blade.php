<div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden my-4">
  <div class="flex p-4">
    <!-- Logo de la tienda -->
    <img src="{{ $product->store_logo_url }}" alt="Logo tienda" class="h-12 w-12 object-contain mr-4">
    <div class="flex-grow">
      <h2 class="text-lg font-semibold text-gray-800">{{ $product->store_name }}</h2>
      <p class="text-sm text-gray-600">{{ $product->notes }}</p>
    </div>
  </div>

  <div class="border-t px-4 py-2 grid grid-cols-2 gap-2 text-sm text-gray-700">
    <div>➤ Coste envío:</div>
    <div class="text-right">
      <div>{{ $product->shipping_cost }}</div>
      <div class="mt-1 text-xs text-gray-500">transporte</div>
    </div>
  </div>

  <div class="border-t px-4 py-4 flex items-end justify-between">
    <div>
      <div class="text-xs text-gray-500">Precio total</div>
      <div class="text-lg font-bold text-gray-800">{{ $product->total_price }}</div>
    </div>
    <div class="text-center">
      <div class="text-xs text-gray-500">Precio por unidad</div>
      <div class="text-2xl font-extrabold text-blue-600">{{ $product->unit_price }}</div>
    </div>
  </div>
</div>
