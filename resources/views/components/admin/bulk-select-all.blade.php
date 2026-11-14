<input type="checkbox" aria-label="انتخاب همه" :checked="allSelected" @change="toggleAll($event.target.checked)"
       class="size-4 rounded border-neutral-300 accent-brand-accent-dark">
