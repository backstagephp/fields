/**
 * Prevents scroll-to-top when adding blocks to a Filament Builder.
 *
 * When a block is added, the Builder's add action calls collapsed(false) which
 * expands all items. Livewire's DOM morph of the expanded sections causes the
 * browser to reset scroll position to 0.
 *
 * The fix: lock the body in place with position:fixed before the morph, then
 * restore the original scroll position after.
 *
 * Only targets builder actions (identified by the "block" param in mountAction
 * calls) so other Livewire interactions are unaffected.
 */
function registerBuilderScrollLock() {
    let savedScroll = null

    Livewire.hook('commit', ({ commit, succeed }) => {
        // Builder add/addBetween actions pass { block: "..." } as the second param
        const isBuilderAction = commit.calls.some(
            (call) =>
                call.method === 'mountAction' &&
                call.params?.[1]?.block !== undefined,
        )

        if (!isBuilderAction) {
            return
        }

        savedScroll = document.documentElement.scrollTop

        if (savedScroll > 0) {
            // Freeze the viewport so the morph can't affect scroll
            document.body.style.position = 'fixed'
            document.body.style.top = `-${savedScroll}px`
            document.body.style.width = '100%'
        }

        succeed(() => {
            if (savedScroll !== null && savedScroll > 0) {
                const y = savedScroll
                savedScroll = null

                // Unfreeze after the morph completes and restore scroll
                setTimeout(() => {
                    document.body.style.position = ''
                    document.body.style.top = ''
                    document.body.style.width = ''
                    document.documentElement.scrollTop = y
                }, 50)
            }
        })
    })
}

if (window.Livewire) {
    registerBuilderScrollLock()
} else {
    document.addEventListener('livewire:init', registerBuilderScrollLock)
}
