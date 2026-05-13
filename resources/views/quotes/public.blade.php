<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devis {{ $quote->number }} — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-full">

<div class="max-w-3xl mx-auto px-4 py-10">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div class="text-xl font-bold text-indigo-600">{{ config('app.name') }}</div>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $quote->statusColor() }}">
            {{ $quote->statusLabel() }}
        </span>
    </div>

    @foreach(['success', 'error', 'info'] as $type)
    @if(session($type))
    @php $colors = ['success' => 'bg-green-50 border-green-200 text-green-700', 'error' => 'bg-red-50 border-red-200 text-red-700', 'info' => 'bg-blue-50 border-blue-200 text-blue-700']; @endphp
    <div class="mb-4 px-4 py-3 {{ $colors[$type] }} border rounded-lg text-sm">{{ session($type) }}</div>
    @endif
    @endforeach

    @if($quote->isExpired())
    <div class="mb-5 px-4 py-3 bg-orange-50 border border-orange-200 text-orange-700 rounded-lg text-sm">
        Ce devis a expiré le {{ $quote->expires_at->format('d/m/Y') }}. Contactez-nous pour un nouveau devis.
    </div>
    @elseif($quote->expires_at && $quote->isPending())
    <div class="mb-5 px-4 py-3 bg-amber-50 border border-amber-100 text-amber-700 rounded-lg text-sm">
        Ce devis est valable jusqu'au <strong>{{ $quote->expires_at->format('d/m/Y') }}</strong>.
    </div>
    @endif

    {{-- Quote info --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-5">
        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <p class="text-xs text-gray-400 uppercase font-semibold mb-2">De</p>
                <div class="font-semibold text-gray-800">{{ config('app.name') }}</div>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase font-semibold mb-2">Pour</p>
                <div class="font-semibold text-gray-800">{{ $quote->user->full_name }}</div>
                @if($quote->user->company)<div class="text-gray-500 text-sm">{{ $quote->user->company }}</div>@endif
                <div class="text-gray-400 text-sm">{{ $quote->user->email }}</div>
            </div>
        </div>

        <div class="border-t border-gray-100 pt-4 flex justify-between text-sm">
            <div>
                <span class="text-gray-400">Référence :</span>
                <span class="font-mono font-medium ml-1">{{ $quote->number }}</span>
            </div>
            @if($quote->subject)
            <div>
                <span class="text-gray-400">Objet :</span>
                <span class="ml-1">{{ $quote->subject }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Items --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-5">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Prestation</th>
                    <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Qté</th>
                    <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">P.U.</th>
                    <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Total HT</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($quote->items as $item)
                <tr>
                    <td class="px-5 py-3">
                        <div class="font-medium text-gray-800">{{ $item->description }}</div>
                        @if($item->details)<div class="text-xs text-gray-400">{{ $item->details }}</div>@endif
                        @if($item->discount_amount > 0)
                        <div class="text-xs text-green-600">Remise : {{ $item->discount_type === 'percent' ? $item->discount_amount . '%' : number_format($item->discount_amount, 2) . ' €' }}</div>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right text-gray-600">{{ $item->quantity }} {{ $item->unit }}</td>
                    <td class="px-5 py-3 text-right text-gray-600">{{ number_format($item->unit_price, 2) }} €</td>
                    <td class="px-5 py-3 text-right font-semibold text-gray-800">{{ number_format($item->total, 2) }} €</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="px-5 py-4 border-t border-gray-50 flex justify-end">
            <div class="w-64 space-y-2 text-sm">
                <div class="flex justify-between text-gray-600">
                    <span>Sous-total HT</span>
                    <span>{{ number_format($quote->subtotal, 2) }} €</span>
                </div>
                @if($quote->discount_amount > 0)
                <div class="flex justify-between text-green-600">
                    <span>Remise globale</span>
                    <span>- {{ $quote->discount_type === 'percent' ? $quote->discount_amount . '%' : number_format($quote->discount_amount, 2) . ' €' }}</span>
                </div>
                @endif
                <div class="flex justify-between font-bold text-gray-900 border-t border-gray-100 pt-2 text-base">
                    <span>Total HT</span>
                    <span>{{ number_format($quote->total, 2) }} {{ $quote->currency }}</span>
                </div>
                @if($quote->deposit_percent > 0)
                <div class="text-xs text-gray-500 space-y-1 pt-2 border-t border-gray-50">
                    <div class="flex justify-between font-medium">
                        <span>Acompte à la commande ({{ $quote->deposit_percent }}%)</span>
                        <span>{{ number_format($quote->depositAmount(), 2) }} €</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Solde à la livraison</span>
                        <span>{{ number_format($quote->balanceAmount(), 2) }} €</span>
                    </div>
                </div>
                @endif
                <p class="text-xs text-gray-400">TVA non applicable, art. 293 B du CGI</p>
            </div>
        </div>
    </div>

    @if($quote->notes)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-5">
        <h3 class="font-semibold text-gray-800 mb-2 text-sm">Remarques</h3>
        <p class="text-sm text-gray-600 whitespace-pre-line">{{ $quote->notes }}</p>
    </div>
    @endif

    {{-- Actions --}}
    @if($quote->isPending() && ! $quote->isExpired())
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6" x-data="{ action: '' }">
        <h3 class="font-semibold text-gray-800 mb-2">Votre réponse</h3>
        <p class="text-sm text-gray-500 mb-5">
            En acceptant ce devis, vous confirmez avoir pris connaissance des prestations décrites et vous engagez à régler le montant indiqué.
        </p>

        {{-- Comment field --}}
        <div x-show="action !== ''" class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Message <span class="text-gray-400 font-normal">(optionnel)</span>
            </label>
            <textarea id="client_comment" name="client_comment" rows="3" placeholder="Remarques, questions..."
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"></textarea>
        </div>

        {{-- CGV --}}
        @if(!empty($cgvPath))
        <div x-show="action === 'accept'" class="mb-4">
            <label class="flex items-start gap-2 text-sm text-gray-700 cursor-pointer">
                <input type="checkbox" id="cgv_accepted" class="mt-0.5 rounded border-gray-300 text-green-600" required>
                <span>J'ai lu et j'accepte les
                    <a href="{{ Storage::url($cgvPath) }}" target="_blank" class="text-indigo-600 underline">Conditions Générales de Vente</a>
                </span>
            </label>
        </div>
        @endif

        <div class="flex flex-wrap gap-3">
            {{-- Accept --}}
            <form method="POST" action="{{ route('quotes.accept', $quote->access_token) }}"
                id="form-accept"
                @submit.prevent="
                    const cgv = document.getElementById('cgv_accepted');
                    if (cgv && !cgv.checked) { alert('Veuillez accepter les CGV.'); return; }
                    if (!confirm('Accepter ce devis pour {{ number_format($quote->total, 2) }} € ?')) return;
                    document.getElementById('hidden_comment_accept').value = document.getElementById('client_comment')?.value ?? '';
                    document.getElementById('hidden_cgv').value = (cgv && cgv.checked) ? '1' : '0';
                    $el.submit();
                ">
                @csrf
                <input type="hidden" name="client_comment" id="hidden_comment_accept">
                <input type="hidden" name="cgv_accepted" id="hidden_cgv" value="0">
                <button type="submit" @click="action = 'accept'"
                    class="px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition">
                    Accepter le devis
                </button>
            </form>

            {{-- Refuse --}}
            <form method="POST" action="{{ route('quotes.refuse', $quote->access_token) }}"
                id="form-refuse"
                @submit.prevent="
                    if (!confirm('Refuser ce devis ?')) return;
                    document.getElementById('hidden_comment_refuse').value = document.getElementById('client_comment')?.value ?? '';
                    $el.submit();
                ">
                @csrf
                <input type="hidden" name="client_comment" id="hidden_comment_refuse">
                <button type="submit" @click="action = 'refuse'"
                    class="px-6 py-3 bg-white border border-red-200 text-red-600 font-medium rounded-lg hover:bg-red-50 transition">
                    Refuser
                </button>
            </form>

            <a href="{{ route('quotes.download-pdf', $quote->access_token) }}"
               class="px-6 py-3 bg-white border border-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                Télécharger PDF
            </a>
        </div>
    </div>
    @elseif($quote->status === 'accepted')
    <div class="bg-green-50 border border-green-200 rounded-xl p-6 text-center">
        <div class="text-green-800 font-bold text-lg mb-1">Devis accepté</div>
        <p class="text-green-700 text-sm">Merci pour votre confiance ! Nous reviendrons vers vous très prochainement.</p>
    </div>
    @elseif($quote->status === 'refused')
    <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 text-center">
        <div class="text-gray-700 font-bold text-lg mb-1">Devis refusé</div>
        <p class="text-gray-500 text-sm">N'hésitez pas à nous recontacter pour discuter de votre projet.</p>
    </div>
    @else
    <div class="text-center">
        <a href="{{ route('quotes.download-pdf', $quote->access_token) }}"
           class="inline-flex px-6 py-3 bg-white border border-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
            Télécharger le devis PDF
        </a>
    </div>
    @endif

    <p class="text-center text-xs text-gray-400 mt-8">{{ config('app.name') }} — Devis {{ $quote->number }} émis le {{ $quote->created_at->format('d/m/Y') }}</p>
</div>

</body>
</html>
