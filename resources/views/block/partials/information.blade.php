<div class="flex flex-wrap">
	<div class="w-full md:w-2/3 md:pr-4">
		<tabs inline-template>
			<div class="box">
				<div class="flex flex-wrap items-start justify-between mb-8">
					<div class="w-full md:w-1/2">
						<h2 class="box-title mb-0">Block Information</h2>
					</div>

					<div class="w-full md:w-auto mt-4 md:mt-0 flex items-center md:justify-end text-blue-dark font-medium cursor-pointer tracking-wide text-center" @click="toggleTab(true)">
						<div v-if="current != true" class="h-4 mr-2">
							@svg('arrow-down', 'stroke-current')
						</div>

						<span v-if="current != true">Show more</span>

						<div v-if="current == true" class="h-4 mr-2" v-cloak>
							@svg('arrow-up', 'stroke-current')
						</div>

						<span v-if="current == true" v-cloak>Show less</span>
					</div>
				</div>

				<div class="mb-4">
					<div class="flex flex-wrap justify-between items-start">
						<div class="mb-4 md:mb-0 w-full md:w-1/2">
							<strong class="info-label">Date and time</strong>
							<span class="info-value">{{ $block->getProperties()->get('time') }} UTC</span>
						</div>

						<div class="w-full md:w-1/2">
							<strong class="info-label">State</strong>
							<span class="info-value">{{ ucfirst($block->getProperties()->get('state')) }}</span>
						</div>
					</div>
				</div>

				<div class="mb-4">
					<strong class="info-label">Hash</strong>

					<a href="{{ route('block', ['address_or_hash' => $block->getProperties()->get('hash')]) }}" rel="nofollow" class="leading-normal opacity-75 block break-words">{{ $block->getProperties()->get('hash') }}</a>
				</div>

				<div class="mb-4">
					<strong class="info-label">Address</strong>

					<a href="/block/{{ $block->getProperties()->get('balance_address') }}" rel="nofollow" class="leading-normal opacity-75 block break-words">{{ $block->getProperties()->get('balance_address') }}</a>
				</div>

				<div class="mb-4">
					<div class="flex flex-wrap justify-between items-start">
						<div class="mb-4 md:mb-0 w-full md:w-1/2">
							<strong class="info-label">Difficulty</strong>
						<span class="info-value block break-words">{{ $block->getProperties()->get('difficulty') }}</span>
						</div>

						<div class="w-full md:w-1/2">
							<strong class="info-label">Kind</strong>
							<span class="info-value">{{ $block->isMainBlock() ? 'Main block' : ($block->isTransactionBlock() ? 'Transaction block' : 'Wallet') }}</span>
						</div>
					</div>
				</div>

				<div v-if="current == true" v-cloak>
					<div class="mb-4">
						<div class="flex flex-wrap justify-between items-start">
							<div class="mb-4 md:mb-0 w-full md:w-1/2">
								<strong class="info-label">Timestamp</strong>
							<span class="info-value block break-words">{{ $block->getProperties()->get('timestamp') }}</span>
							</div>

							<div class="w-full md:w-1/2">
								<strong class="info-label">Flags, file pos</strong>
								<span class="info-value">{{ $block->getProperties()->get('flags') }}, {{ $block->getProperties()->get('file_pos') }}</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</tabs>
	</div>

	@if ($block->isTransactionBlock())
		<div class="w-full md:w-1/3 md:pl-4">
			<div class="box">
				<h3 class="box-title">Summary</h3>

				<div class="mb-4">
					<div class="flex items-center justify-between">
						<div class="mr-4">
							<div class="info-label">Total fee</div>
							<span class="info-value">{{ number_format($block->getTransactions()->getTotalFee(), 9) }}</span>
						</div>
					</div>
				</div>

				<div class="mb-4">
					<div class="flex items-center justify-between">
						<div class="mr-4">
							<div class="info-label">{{ $count = $block->getTransactions()->inputs()->count() }} input{{ $count > 1 ? 's' : '' }}</div>
							<span class="info-value">{{ number_format($block->getTransactions()->getInputsSum(), 9) }}</span>
						</div>
					</div>
				</div>

				<div class="mb-4">
					<div class="flex items-center justify-between">
						<div class="mr-4">
							<div class="info-label">{{ $count = $block->getTransactions()->outputs()->count() }} output{{ $count > 1 ? 's' : '' }}</div>
							<span class="info-value">{{ number_format($block->getTransactions()->getOutputsSum(), 9) }}</span>
						</div>
					</div>
				</div>
			</div>
		</div>
	@else
		<div class="w-full md:w-1/3 md:pl-4">
			<div class="box">
				<h3 class="box-title">Balances</h3>

				<div class="mb-4">
					<div class="flex items-center justify-between">
						<div class="mr-4">
							<div class="info-label">Balance</div>
							<span class="info-value">{{ number_format($block->getBalance(), 9) }}</span>
						</div>

						@include('support.value-change', ['valueChange' => $balanceChange, 'name' => 'Balance', 'change' => 'since 24 hours ago', 'type' => 'value'])
					</div>

					<modal inline-template>
						<div>
							<div class="w-full flex items-center text-blue-dark font-medium cursor-pointer tracking-wide text-center text-sm" @click="toggleModal">
								<div class="h-4 mr-2">
									@svg('chart', ['class' => 'stroke-current w-3 h-3'])
								</div>

								<span>Details</span>
							</div>

							@include('block.partials.balance-modal')
						</div>
					</modal>
				</div>

				<div class="mb-4">
					<div class="flex items-center justify-between">
						<div class="mr-4">
							<div class="info-label">Total Earnings</div>
							<span class="info-value">{{ number_format($block->getTotalEarnings(), 9) }}</span>
						</div>

						@include('support.value-change', ['valueChange' => $earningChange, 'name' => 'Earnings', 'change' => 'since 24 hours ago', 'type' => 'value'])
					</div>

					<modal inline-template>
						<div>
							<div class="w-full flex items-center text-blue-dark font-medium cursor-pointer tracking-wide text-center text-sm" @click="toggleModal">
								<div class="h-4 mr-2">
									@svg('chart', ['class' => 'stroke-current w-3 h-3'])
								</div>

								<span>Details</span>
							</div>

							@include('block.partials.earnings-modal')
						</div>
					</modal>
				</div>

				<div>
					<div class="flex items-center justify-between">
						<div class="mr-4">
							<div class="info-label">Total Spendings</div>
							<span class="info-value">{{ number_format($block->getTotalSpendings(), 9) }}</span>
						</div>

						@include('support.value-change', ['valueChange' => $spendingChange, 'name' => 'Spendings', 'change' => 'since 24 hours ago', 'type' => 'value'])
					</div>

					<modal inline-template>
						<div>
							<div class="w-full flex items-center text-blue-dark font-medium cursor-pointer tracking-wide text-center text-sm" @click="toggleModal">
								<div class="h-4 mr-2">
									@svg('chart', ['class' => 'stroke-current w-3 h-3'])
								</div>

								<span>Details</span>
							</div>

							@include('block.partials.spendings-modal')
						</div>
					</modal>
				</div>
			</div>
		</div>
	@endif
</div>
