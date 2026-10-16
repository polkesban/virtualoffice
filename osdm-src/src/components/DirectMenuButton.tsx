import React from 'react';
import { motion } from 'motion/react';
import { MenuItem, ThemeConfig } from '../types';
import { getIconComponent } from '../utils/iconMap';
import { ExternalLink, ChevronRight, Zap, Sparkles, Lock } from 'lucide-react';

interface DirectMenuButtonProps {
  menu: MenuItem;
  theme: ThemeConfig;
  onMenuClick?: (menu: MenuItem) => void;
  isPreviewMode?: boolean;
  showClickBadge?: boolean;
}

export const DirectMenuButton: React.FC<DirectMenuButtonProps> = ({
  menu,
  theme,
  onMenuClick,
  isPreviewMode = false,
  showClickBadge = false,
}) => {
  if (!menu.isActive && !isPreviewMode) {
    return null;
  }

  const handleClick = (e: React.MouseEvent<HTMLAnchorElement | HTMLButtonElement>) => {
    if (isPreviewMode) {
      e.preventDefault();
      return;
    }
    if (menu.isProtected && menu.pinCode && menu.pinCode.trim() !== '') {
      e.preventDefault();
      if (onMenuClick) {
        onMenuClick(menu);
      }
      return;
    }
    const isInternalAction =
      menu.url?.startsWith('#') ||
      menu.type === 'custom' ||
      menu.id === 'menu-wfa-bimbingan' ||
      menu.id === 'menu-kebugaran-jasmani' ||
      menu.id === 'menu-daftar-hadir-rapat' ||
      menu.id === 'menu-mooc-asn' ||
      menu.title?.toLowerCase().includes('wfa') ||
      menu.title?.toLowerCase().includes('kebugaran') ||
      menu.title?.toLowerCase().includes('daftar hadir') ||
      menu.title?.toLowerCase().includes('presensi rapat') ||
      menu.title?.toLowerCase().includes('mooc') ||
      menu.title?.toLowerCase().includes('asn berintegritas');

    if (isInternalAction) {
      e.preventDefault();
      if (onMenuClick) {
        onMenuClick(menu);
      }
      return;
    }
    if (onMenuClick) {
      onMenuClick(menu);
    }
  };

  // Determine size classes
  const getSizeStyles = () => {
    switch (menu.size) {
      case 'compact':
        return {
          container: 'py-3 px-3.5 min-h-[54px] text-sm',
          iconBox: 'w-10 h-10 min-w-[40px]',
          iconSize: 'w-8 h-8 max-w-[34px] max-h-[34px]',
          title: 'text-sm font-semibold',
          subtitle: 'text-xs opacity-80',
          badge: 'text-[10px] px-1.5 py-0.5',
        };
      case 'large':
        return {
          container: 'py-4.5 px-5 min-h-[78px]',
          iconBox: 'w-13 h-13 min-w-[52px]',
          iconSize: 'w-10 h-10 max-w-[44px] max-h-[44px]',
          title: 'text-base sm:text-lg font-bold tracking-tight',
          subtitle: 'text-xs sm:text-sm opacity-85 mt-0.5',
          badge: 'text-xs px-2.5 py-1 font-bold',
        };
      case 'featured':
        return {
          container: 'py-5.5 px-6 min-h-[94px] ring-2 ring-offset-2 ring-offset-neutral-950',
          iconBox: 'w-15 h-15 min-w-[60px]',
          iconSize: 'w-12 h-12 max-w-[52px] max-h-[52px]',
          title: 'text-lg sm:text-xl font-extrabold tracking-tight',
          subtitle: 'text-xs sm:text-sm font-medium opacity-90 mt-1',
          badge: 'text-xs px-3 py-1 font-extrabold uppercase tracking-wider shadow-sm',
        };
      case 'bento-square':
        return {
          container: 'p-4 min-h-[135px] flex-col justify-between items-start text-left col-span-1',
          iconBox: 'w-12 h-12 min-w-[48px] mb-2',
          iconSize: 'w-9 h-9 max-w-[38px] max-h-[38px]',
          title: 'text-sm sm:text-base font-bold line-clamp-2',
          subtitle: 'text-xs opacity-80 line-clamp-1 mt-0.5',
          badge: 'text-[10px] px-2 py-0.5 self-start mb-1',
        };
      case 'bento-wide':
        return {
          container: 'p-4.5 sm:p-5 min-h-[114px] col-span-2 flex-row justify-between items-center',
          iconBox: 'w-13 h-13 min-w-[52px]',
          iconSize: 'w-10 h-10 max-w-[42px] max-h-[42px]',
          title: 'text-base sm:text-lg font-bold',
          subtitle: 'text-xs sm:text-sm opacity-85 mt-0.5',
          badge: 'text-xs px-2.5 py-0.5 font-bold',
        };
      case 'medium':
      default:
        return {
          container: 'py-3.5 px-4.5 min-h-[64px]',
          iconBox: 'w-11 h-11 min-w-[44px]',
          iconSize: 'w-9 h-9 max-w-[38px] max-h-[38px]',
          title: 'text-sm sm:text-base font-semibold',
          subtitle: 'text-xs opacity-85 mt-0.5',
          badge: 'text-[11px] px-2 py-0.5 font-semibold',
        };
    }
  };

  const sizeStyle = getSizeStyles();

  // Background styling
  const getBackgroundStyle = (): React.CSSProperties => {
    const isGrad = menu.isGradient;
    const fromColor = menu.bgColor || '#1e293b';
    const toColor = menu.gradientTo || fromColor;
    const angle = menu.gradientAngle || 135;

    let background = fromColor;
    if (isGrad) {
      background = `linear-gradient(${angle}deg, ${fromColor} 0%, ${toColor} 100%)`;
    }

    return {
      background,
      color: menu.textColor || '#ffffff',
      borderColor: menu.borderColor || 'transparent',
    };
  };

  // Animation variants
  const getAnimationClass = () => {
    switch (menu.animation) {
      case 'pulse':
        return 'animate-pulse';
      case 'bounce':
        return 'animate-bounce';
      case 'glow':
        return 'shadow-[0_0_25px_rgba(255,255,255,0.25)]';
      case 'shimmer':
        return 'relative overflow-hidden before:absolute before:inset-0 before:-translate-x-full before:animate-[shimmer_2s_infinite] before:bg-gradient-to-r before:from-transparent before:via-white/15 before:to-transparent';
      default:
        return '';
    }
  };

  const isBentoSquare = menu.size === 'bento-square';

  return (
    <motion.div
      layout
      initial={{ opacity: 0, y: 8 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, scale: 0.95 }}
      whileHover={{ scale: 1.015, translateY: -2 }}
      whileTap={{ scale: 0.985 }}
      transition={{ duration: 0.18 }}
      className={`group w-full relative select-none ${menu.size === 'bento-wide' ? 'sm:col-span-2' : ''} ${menu.size === 'bento-square' ? 'col-span-1' : ''}`}
    >
      <a
        href={menu.url || '#'}
        target={menu.openInNewTab ? '_blank' : '_self'}
        rel="noopener noreferrer"
        onClick={handleClick}
        style={getBackgroundStyle()}
        className={`
          flex items-center justify-between gap-3.5 w-full text-left
          ${theme.cardRadius || 'rounded-2xl'}
          ${theme.cardGlassEffect ? 'backdrop-blur-md shadow-md' : 'shadow-sm'}
          ${sizeStyle.container}
          ${getAnimationClass()}
          border transition-all duration-200 cursor-pointer
          hover:shadow-lg hover:brightness-105 active:brightness-95
          ${!menu.isActive ? 'opacity-50 border-dashed border-red-500/50' : ''}
        `}
      >
        {/* Main Content Area */}
        <div className={`flex ${isBentoSquare ? 'flex-col items-start gap-2 w-full' : 'items-center gap-3.5 flex-1 min-w-0'}`}>
          {/* 3D Pop-out Icon Container (Option B: Frosted Glass + 3D Breakout) */}
          <div className="relative shrink-0 flex items-center justify-center my-0.5">
            {/* Ambient Back Glow / Halo */}
            <div className="absolute inset-0 rounded-2xl bg-white/30 blur-md scale-105 pointer-events-none opacity-60 group-hover:opacity-100 group-hover:scale-125 transition-all duration-300" />

            {/* Frosted Glass Pedestal (Wadah Kaca Semi-Transparan Bercahaya) */}
            <div
              className={`
                ${sizeStyle.iconBox}
                relative flex items-center justify-center rounded-2xl
                bg-gradient-to-br from-white/35 via-white/20 to-white/10
                backdrop-blur-md
                border border-white/50
                shadow-[inset_0_1.5px_2px_rgba(255,255,255,0.75),0_4px_14px_rgba(0,0,0,0.22)]
                ring-1 ring-white/30
                transition-all duration-300
                group-hover:border-white/80 group-hover:from-white/40 group-hover:to-white/20
                group-hover:shadow-[inset_0_1.5px_2px_rgba(255,255,255,0.9),0_6px_20px_rgba(0,0,0,0.32)]
                overflow-visible
              `}
            >
              {/* 3D Pop-Out Breakout Icon Element (Efek Ikon Keluar & Timbul dari Wadah) */}
              <div
                className="relative z-10 flex items-center justify-center
                  transform scale-140 -translate-y-1.5
                  transition-all duration-300 ease-out
                  group-hover:scale-160 group-hover:-translate-y-2.5 group-hover:-rotate-3
                  filter drop-shadow-[0_6px_8px_rgba(0,0,0,0.45)] drop-shadow-[0_2px_3px_rgba(0,0,0,0.35)]
                  select-none pointer-events-none"
              >
                {getIconComponent(menu.iconName, sizeStyle.iconSize || 'w-8 h-8')}
              </div>
            </div>
          </div>

          {/* Text Details */}
          <div className="flex-1 min-w-0">
            {/* Optional Badge */}
            {menu.badgeText && (
              <div className="mb-1 flex items-center gap-1">
                <span
                  style={{
                    backgroundColor: menu.badgeBgColor || '#f59e0b',
                    color: menu.badgeTextColor || '#000000',
                  }}
                  className={`inline-flex items-center gap-1 rounded-full font-bold uppercase tracking-wider ${sizeStyle.badge}`}
                >
                  <Sparkles className="w-2.5 h-2.5 shrink-0" />
                  {menu.badgeText}
                </span>
              </div>
            )}

            <div className="flex items-center gap-2 flex-wrap">
              <h3 className={`${sizeStyle.title} truncate leading-snug`}>
                {menu.title}
              </h3>
              {menu.priceTag && (
                <span className="shrink-0 text-xs px-2 py-0.5 rounded-full bg-white/20 font-mono font-bold">
                  {menu.priceTag}
                </span>
              )}
            </div>

            {menu.subtitle && (
              <p className={`${sizeStyle.subtitle} leading-relaxed line-clamp-2`}>
                {menu.subtitle}
              </p>
            )}
          </div>
        </div>

        {/* Right Action Indicator */}
        {!isBentoSquare ? (
          <div className="flex items-center gap-2 shrink-0 pl-1">
            {showClickBadge && (
              <span className="hidden sm:inline-flex items-center gap-1 text-[11px] font-mono px-2 py-0.5 rounded-full bg-black/30 border border-white/10 text-white/90">
                <Zap className="w-2.5 h-2.5 text-amber-400" />
                {menu.clickCount} klik
              </span>
            )}
            <div className="w-7 h-7 rounded-full bg-black/15 flex items-center justify-center group-hover:translate-x-1 transition-transform border border-white/10">
              {menu.isProtected ? (
                <Lock className="w-3.5 h-3.5 text-amber-300 opacity-90" />
              ) : (
                <ChevronRight className="w-4 h-4 opacity-80" />
              )}
            </div>
          </div>
        ) : menu.isProtected ? (
          <div className="absolute top-3 right-3 w-6 h-6 rounded-full bg-black/20 flex items-center justify-center border border-white/10">
            <Lock className="w-3 h-3 text-amber-300 opacity-90" />
          </div>
        ) : null}
      </a>

      {/* Admin quick inactive indicator */}
      {!menu.isActive && (
        <span className="absolute -top-2 -right-2 text-[10px] bg-red-600 text-white px-2 py-0.5 rounded-full font-bold shadow-md">
          Nonaktif
        </span>
      )}
    </motion.div>
  );
};
