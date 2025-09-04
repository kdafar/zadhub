import { NextRequest, NextResponse } from 'next/server'

export async function POST(req: NextRequest) {
  const body = await req.json().catch(() => null)
  const secret = process.env.REVALIDATE_SECRET
  if (!body || body.secret !== secret) {
    return NextResponse.json({ ok: false, message: 'Unauthorized' }, { status: 401 })
  }

  const paths: string[] = Array.isArray(body.paths) ? body.paths : []
  for (const p of paths) {
    try {
      await (global as any).revalidatePath?.(p)
    } catch {}
  }
  return NextResponse.json({ revalidated: true, paths })
}
