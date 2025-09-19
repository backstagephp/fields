import { Mark } from '@tiptap/core'

export default Mark.create({
    name: 'jumpAnchor',

    addOptions() {
        return {
            HTMLAttributes: {},
        }
    },

    addAttributes() {
        return {
            'anchorId': {
                default: null,
                parseHTML: element => element.getAttribute('id') || element.getAttribute('data-anchor-id'),
                renderHTML: attributes => {
                    if (!attributes['anchorId']) {
                        return {}
                    }
                    
                    const result = {}
                    const attributeType = attributes['attributeType'] || 'id'
                    const customAttribute = attributes['customAttribute']
                    
                    if (attributeType === 'id') {
                        result['id'] = attributes['anchorId']
                        result['data-anchor-id'] = attributes['anchorId']
                    } else if (attributeType === 'custom' && customAttribute) {
                        result[customAttribute] = attributes['anchorId']
                        // Don't add data-anchor-id when using custom attributes
                    }
                    
                    return result
                },
            },
            'attributeType': {
                default: 'id',
                parseHTML: element => {
                    if (element.hasAttribute('id')) return 'id'
                    return 'custom'
                },
                renderHTML: attributes => {
                    return {}
                },
            },
            'customAttribute': {
                default: null,
                parseHTML: element => {
                    // Find the first non-standard attribute
                    const attrs = element.attributes
                    for (let i = 0; i < attrs.length; i++) {
                        const attr = attrs[i]
                        if (attr.name !== 'id' && attr.name !== 'data-anchor-id' && attr.name !== 'class') {
                            return attr.name
                        }
                    }
                    return null
                },
                renderHTML: attributes => {
                    return {}
                },
            },
        }
    },

    addCommands() {
        return {
            setJumpAnchor:
                (attributes) =>
                ({ commands }) => {
                    return commands.setMark(this.name, attributes)
                },
            unsetJumpAnchor:
                () =>
                ({ commands }) => {
                    return commands.unsetMark(this.name)
                },
            toggleJumpAnchor:
                (attributes) =>
                ({ commands }) => {
                    return commands.toggleMark(this.name, attributes)
                },
        }
    },

    parseHTML() {
        return [
            {
                tag: 'span[data-anchor-id]',
            },
            {
                tag: 'span[id]',
            },
            {
                tag: 'span',
                getAttrs: element => {
                    // Check for any custom attribute that looks like an anchor
                    const attrs = element.attributes
                    for (let i = 0; i < attrs.length; i++) {
                        const attr = attrs[i]
                        if (attr.name.startsWith('data-') && attr.name !== 'data-anchor-id') {
                            return { 
                                'anchorId': attr.value,
                                'attributeType': 'custom',
                                'customAttribute': attr.name
                            }
                        }
                    }
                    return false
                },
            },
        ]
    },

    renderHTML({ HTMLAttributes }) {
        return ['span', HTMLAttributes, 0]
    },
})